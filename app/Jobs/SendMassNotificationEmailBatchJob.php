<?php

namespace App\Jobs;

use App\Mail\MassNotificationEmailAttachments;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\EmailDispatchFailureKind;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\EmailDispatchFailureClassifier;
use App\Services\Marketing\MarketingApiHttpFactory;
use App\Services\Marketing\MarketingApiTraceRecorder;
use App\Services\Marketing\MassEmailCircuitBreaker;
use App\Services\Marketing\NotificationDispatchFailureResolver;
use App\Services\Marketing\NotificationDispatchLogData;
use App\Services\Marketing\NotificationDispatchLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMassNotificationEmailBatchJob implements ShouldQueue
{
    use Queueable;

    /**
     * Los reintentos no se delegan en la cola: cuando un lote falla a medias, reintentarlo
     * entero volvería a escribir a quienes ya lo recibieron. En su lugar se despacha una
     * copia con los destinatarios pendientes (ver `reschedule()`), así que a la cola le
     * basta un intento por job.
     */
    public int $tries = 1;

    /** Tope de reprogramaciones por fallo del proveedor antes de darlo por perdido. */
    private const int MaxAttempts = 6;

    /** Tope de esperas por freno abierto, para que un freno que se renueva no reencole eternamente. */
    private const int MaxDeferrals = 24;

    /**
     * El POST al endpoint bulk espera hasta `services.marketing_api.email_timeout` segundos
     * porque el API entrega los correos por SMTP antes de responder. Sin este timeout el
     * worker aplica su valor por defecto (60 s) y mata el job a media entrega: los correos
     * ya salieron del lado del API, pero aquí no queda traza, ni log de despacho, ni
     * reprogramación de los pendientes.
     */
    public int $timeout;

    /**
     * @param  list<string>  $emails
     */
    public function __construct(
        public int $massNotificationId,
        public array $emails,
        public string $subject,
        public string $copy,
        public int $batchNumber,
        public int $totalBatches,
        public int $sentById,
        public string $source,
        public ?string $dispatchRunId = null,
        public int $attempt = 1,
        public int $deferrals = 0,
    ) {
        $this->onQueue('email');
        $this->timeout = (int) config('services.marketing_api.email_timeout', 300) + 60;
    }

    public function handle(
        NotificationDispatchLogger $dispatchLogger,
        MarketingApiTraceRecorder $apiTraceRecorder,
        DispatchProgressTracker $progressTracker,
        MassEmailCircuitBreaker $circuitBreaker,
        EmailDispatchFailureClassifier $classifier,
    ): void {
        $notification = MassNotification::query()->find($this->massNotificationId);

        if ($notification === null) {
            return;
        }

        $source = NotificationDispatchSource::tryFrom($this->source) ?? NotificationDispatchSource::MassAudience;

        // El proveedor ya dijo que no acepta más correo: reprogramar sin llamar al API.
        // Esto es lo que evita que los lotes restantes se quemen en cadena.
        if ($circuitBreaker->isOpen()) {
            $this->deferWhileCircuitIsOpen($circuitBreaker, $progressTracker, $dispatchLogger, $source);

            return;
        }

        if ($this->dispatchRunId !== null) {
            $progressTracker->markBatchProcessing(
                runId: $this->dispatchRunId,
                channelLabel: BirthdayNotificationChannel::Email->getLabel(),
                batchNumber: $this->batchNumber,
                totalBatches: $this->totalBatches,
                recipientCount: count($this->emails),
            );
        }

        $endpoint = $this->bulkEmailsEndpoint();
        $payload = [
            'recipients' => json_encode(array_values($this->emails)),
            'copy' => $this->copy,
            'subject' => $this->subject,
        ];

        try {
            $response = MassNotificationEmailAttachments::attach(
                app(MarketingApiHttpFactory::class)->emailClient(),
                $notification,
            )->post($endpoint, $payload);

            $apiTrace = $apiTraceRecorder->fromResponse(
                label: 'Correo masivo (lote)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            /** @var array{sent?: int, total?: int, success?: bool, message?: string, reason?: string, error?: string, failures?: list<array<string, mixed>>}|null $responsePayload */
            $responsePayload = $response->json();
            $body = is_array($responsePayload) ? $responsePayload : [];
            $sent = (int) ($body['sent'] ?? ($response->successful() ? count($this->emails) : 0));
            $total = (int) ($body['total'] ?? count($this->emails));
            $payloadSuccess = ($body['success'] ?? null) !== false;
            $successful = $response->successful() && $payloadSuccess && $sent > 0;

            $message = $successful
                ? (string) ($body['message'] ?? 'Correos enviados correctamente.')
                : $this->resolveApiErrorMessage($body, $response->body());

            if (! $successful || $sent < $total) {
                $handled = $this->handleFailure(
                    body: $body,
                    fallbackMessage: $message,
                    httpStatus: $response->status(),
                    sent: $sent,
                    total: $total,
                    apiTrace: $apiTrace,
                    source: $source,
                    dispatchLogger: $dispatchLogger,
                    progressTracker: $progressTracker,
                    circuitBreaker: $circuitBreaker,
                    classifier: $classifier,
                );

                if ($handled) {
                    return;
                }
            }

            $dispatchLogger->logChannelResult(
                base: $this->logBase($source, $message, $apiTrace),
                successful: $successful,
                message: $message,
                sent: $sent,
                total: $total,
            );

            if ($this->dispatchRunId !== null) {
                $progressTracker->recordUnitCompletion(
                    runId: $this->dispatchRunId,
                    channelLabel: BirthdayNotificationChannel::Email->getLabel(),
                    sent: $sent,
                    failed: max(0, $total - $sent),
                    detail: $message,
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                    recipientFailures: $this->extractRecipientFailures($body),
                );
            }

            Log::info('Mass notification email batch sent.', [
                'mass_notification_id' => $this->massNotificationId,
                'batch' => $this->batchNumber,
                'total_batches' => $this->totalBatches,
                'attempt' => $this->attempt,
                'recipients' => count($this->emails),
                'sent' => $sent,
                'total' => $total,
                'successful' => $successful,
            ]);
        } catch (ConnectionException $exception) {
            $isTimeout = $apiTraceRecorder->isTimeout($exception);
            $message = $isTimeout
                ? 'El API de correos no confirmó el resultado a tiempo. Es probable que los correos sí se hayan enviado.'
                : 'No se pudo conectar con el API de correos.';

            Log::error('Mass notification email batch could not reach marketing API.', [
                'mass_notification_id' => $this->massNotificationId,
                'batch' => $this->batchNumber,
                'attempt' => $this->attempt,
                'message' => $exception->getMessage(),
            ]);

            $apiTrace = $apiTraceRecorder->fromConnectionError(
                label: 'Correo masivo (lote)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                exception: $exception,
            );

            // Un timeout no confirma nada: reintentar podría duplicar correos ya enviados,
            // así que solo se reprograman los cortes de conexión, donde no salió nada.
            if (! $isTimeout && $this->canRetryAgain()) {
                $this->reschedule(
                    pendingEmails: $this->emails,
                    kind: EmailDispatchFailureKind::Transient,
                    reason: $message,
                    apiTrace: $apiTrace,
                    source: $source,
                    dispatchLogger: $dispatchLogger,
                    progressTracker: $progressTracker,
                    sentInThisAttempt: 0,
                );

                return;
            }

            $dispatchLogger->logChannelResult(
                base: $this->logBase($source, $message, $apiTrace),
                successful: false,
                message: $message,
                sent: 0,
                total: count($this->emails),
            );

            if ($this->dispatchRunId !== null) {
                $progressTracker->recordUnitCompletion(
                    runId: $this->dispatchRunId,
                    channelLabel: BirthdayNotificationChannel::Email->getLabel(),
                    sent: 0,
                    failed: count($this->emails),
                    detail: $message,
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                );
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->dispatchRunId === null) {
            return;
        }

        app(DispatchProgressTracker::class)->recordUnitCompletion(
            runId: $this->dispatchRunId,
            channelLabel: BirthdayNotificationChannel::Email->getLabel(),
            sent: 0,
            failed: count($this->emails),
            detail: $exception?->getMessage() ?? 'Error inesperado al procesar el lote de correo.',
            batchNumber: $this->batchNumber,
            totalBatches: $this->totalBatches,
        );
    }

    /**
     * Decide si el fallo merece otro intento. Devuelve `true` cuando el lote quedó
     * reprogramado y el llamador no debe cerrarlo.
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $apiTrace
     */
    private function handleFailure(
        array $body,
        string $fallbackMessage,
        int $httpStatus,
        int $sent,
        int $total,
        array $apiTrace,
        NotificationDispatchSource $source,
        NotificationDispatchLogger $dispatchLogger,
        DispatchProgressTracker $progressTracker,
        MassEmailCircuitBreaker $circuitBreaker,
        EmailDispatchFailureClassifier $classifier,
    ): bool {
        $diagnosis = $classifier->messageFromResponseBody($body, $fallbackMessage);
        $kind = $classifier->classify($httpStatus, $diagnosis);

        // El proveedor rechazó al remitente entero, no a estos destinatarios: frenar el
        // canal antes de que los lotes siguientes repitan el intento.
        if ($kind->shouldTripCircuit()) {
            $circuitBreaker->trip(
                kind: $kind,
                reason: $diagnosis,
                cooldownSeconds: $kind->cooldownSeconds($this->attempt),
            );
        }

        if (! $kind->isRetryable() || ! $this->canRetryAgain()) {
            return false;
        }

        // Reintentar solo a quienes no recibieron nada. Si el API no detalló los fallos,
        // se asume que ninguno salió salvo que reporte envíos parciales.
        $pending = $classifier->failedAddresses($body);

        if ($pending === []) {
            if ($sent > 0) {
                // Hubo envíos que el API no detalló: reintentar el lote entero duplicaría
                // correos, así que se cierra aquí y el analista decide desde el historial.
                return false;
            }

            $pending = $this->emails;
        }

        $pending = array_values(array_intersect($this->emails, $pending));

        if ($pending === []) {
            return false;
        }

        $this->reschedule(
            pendingEmails: $pending,
            kind: $kind,
            reason: $diagnosis,
            apiTrace: $apiTrace,
            source: $source,
            dispatchLogger: $dispatchLogger,
            progressTracker: $progressTracker,
            sentInThisAttempt: $sent,
        );

        return true;
    }

    /**
     * Despacha una copia del lote con los destinatarios que faltan.
     *
     * No se usa `release()` porque la cola reencola el payload original: volvería a
     * escribir a los destinatarios que ya recibieron el correo en este intento.
     *
     * @param  list<string>  $pendingEmails
     * @param  array<string, mixed>  $apiTrace
     */
    private function reschedule(
        array $pendingEmails,
        EmailDispatchFailureKind $kind,
        string $reason,
        array $apiTrace,
        NotificationDispatchSource $source,
        NotificationDispatchLogger $dispatchLogger,
        DispatchProgressTracker $progressTracker,
        int $sentInThisAttempt,
    ): void {
        $delaySeconds = $kind->cooldownSeconds($this->attempt);
        $retryAt = now()->addSeconds($delaySeconds);
        $nextAttempt = $this->attempt + 1;

        $message = $kind->label().'. Lote reencolado para las '.$retryAt->format('H:i')
            .' con '.count($pendingEmails).' destinatario'.(count($pendingEmails) === 1 ? '' : 's')
            .' pendiente'.(count($pendingEmails) === 1 ? '' : 's')
            .' (intento '.$nextAttempt.' de '.self::MaxAttempts.'). No relances la campaña: duplicarías los correos ya enviados.';

        self::dispatch(
            massNotificationId: $this->massNotificationId,
            emails: $pendingEmails,
            subject: $this->subject,
            copy: $this->copy,
            batchNumber: $this->batchNumber,
            totalBatches: $this->totalBatches,
            sentById: $this->sentById,
            source: $this->source,
            dispatchRunId: $this->dispatchRunId,
            attempt: $nextAttempt,
            deferrals: $this->deferrals,
        )->delay($retryAt);

        $dispatchLogger->logChannelResult(
            base: $this->logBase($source, $message, $apiTrace),
            successful: false,
            message: $message,
            sent: $sentInThisAttempt,
            total: count($this->emails),
        );

        if ($this->dispatchRunId !== null && $sentInThisAttempt > 0) {
            $progressTracker->recordPartialProgress(
                runId: $this->dispatchRunId,
                channelLabel: BirthdayNotificationChannel::Email->getLabel(),
                sent: $sentInThisAttempt,
                detail: $message,
                batchNumber: $this->batchNumber,
                totalBatches: $this->totalBatches,
            );
        } elseif ($this->dispatchRunId !== null) {
            $progressTracker->markJobQueued($this->dispatchRunId, $message);
        }

        Log::warning('Mass notification email batch rescheduled.', [
            'mass_notification_id' => $this->massNotificationId,
            'batch' => $this->batchNumber,
            'kind' => $kind->value,
            'attempt' => $this->attempt,
            'next_attempt' => $nextAttempt,
            'pending_recipients' => count($pendingEmails),
            'retry_at' => $retryAt->toIso8601String(),
            'reason' => $reason,
        ]);
    }

    /**
     * Reprograma el lote sin gastar intentos: el freno está abierto por un fallo previo,
     * así que este lote todavía no ha tenido su oportunidad.
     */
    private function deferWhileCircuitIsOpen(
        MassEmailCircuitBreaker $circuitBreaker,
        DispatchProgressTracker $progressTracker,
        NotificationDispatchLogger $dispatchLogger,
        NotificationDispatchSource $source,
    ): void {
        $delaySeconds = $circuitBreaker->secondsUntilRetry();
        $pauseMessage = $circuitBreaker->pauseMessage() ?? 'Canal de correo en pausa.';

        if ($this->deferrals >= self::MaxDeferrals) {
            $message = 'El canal de correo siguió bloqueado durante demasiado tiempo: '.$pauseMessage;

            $dispatchLogger->logChannelResult(
                base: $this->logBase($source, $message, ['api_calls' => []]),
                successful: false,
                message: $message,
                sent: 0,
                total: count($this->emails),
            );

            if ($this->dispatchRunId !== null) {
                $progressTracker->recordUnitCompletion(
                    runId: $this->dispatchRunId,
                    channelLabel: BirthdayNotificationChannel::Email->getLabel(),
                    sent: 0,
                    failed: count($this->emails),
                    detail: $message,
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                );
            }

            return;
        }

        self::dispatch(
            massNotificationId: $this->massNotificationId,
            emails: $this->emails,
            subject: $this->subject,
            copy: $this->copy,
            batchNumber: $this->batchNumber,
            totalBatches: $this->totalBatches,
            sentById: $this->sentById,
            source: $this->source,
            dispatchRunId: $this->dispatchRunId,
            attempt: $this->attempt,
            deferrals: $this->deferrals + 1,
        )->delay(now()->addSeconds($delaySeconds));

        if ($this->dispatchRunId !== null) {
            $progressTracker->markJobQueued(
                $this->dispatchRunId,
                'Correo · Lote '.$this->batchNumber.'/'.$this->totalBatches.' en espera. '.$pauseMessage,
            );
        }

        Log::info('Mass notification email batch deferred by circuit breaker.', [
            'mass_notification_id' => $this->massNotificationId,
            'batch' => $this->batchNumber,
            'deferrals' => $this->deferrals + 1,
            'retry_in_seconds' => $delaySeconds,
            'reason' => $circuitBreaker->reason(),
        ]);
    }

    private function canRetryAgain(): bool
    {
        return $this->attempt < self::MaxAttempts;
    }

    /**
     * @param  array<string, mixed>  $apiTrace
     */
    private function logBase(
        NotificationDispatchSource $source,
        string $message,
        array $apiTrace,
    ): NotificationDispatchLogData {
        return new NotificationDispatchLogData(
            source: $source,
            status: NotificationDispatchStatus::Failed,
            title: $this->subject,
            summary: $message,
            channel: BirthdayNotificationChannel::Email,
            massNotificationId: $this->massNotificationId,
            sentById: $this->sentById,
            batchNumber: $this->batchNumber,
            totalBatches: $this->totalBatches,
            technicalDetail: $apiTrace,
        );
    }

    private function bulkEmailsEndpoint(): string
    {
        $path = config('services.marketing_api.bulk_emails_path', '/api/emails/bulk');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function resolveApiErrorMessage(array $body, string $rawBody): string
    {
        foreach (['reason', 'message', 'error', 'detail'] as $key) {
            if (filled($body[$key] ?? null)) {
                return (string) $body[$key];
            }
        }

        return filled($rawBody) ? $rawBody : 'El API rechazó el envío.';
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<array{address: string, reason: string, analyst_message: string}>
     */
    private function extractRecipientFailures(array $body): array
    {
        if (! is_array($body['failures'] ?? null) || $body['failures'] === []) {
            return [];
        }

        $resolver = app(NotificationDispatchFailureResolver::class);

        return collect($body['failures'])
            ->map(function (mixed $row) use ($resolver): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $address = $row['email'] ?? $row['to'] ?? $row['address'] ?? null;

                if (! filled($address) || ! is_string($address)) {
                    return null;
                }

                $reason = filled($row['reason'] ?? null) ? (string) $row['reason'] : 'Error de entrega de correo';
                $guidance = $resolver->resolve(
                    status: NotificationDispatchStatus::Failed,
                    technicalMessage: $reason,
                    channel: BirthdayNotificationChannel::Email,
                );

                return [
                    'address' => $address,
                    'reason' => $reason,
                    'analyst_message' => $guidance['analyst_message'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
