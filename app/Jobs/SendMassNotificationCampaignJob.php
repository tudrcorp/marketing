<?php

namespace App\Jobs;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\EmailDispatchFailureKind;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\EmailDispatchFailureClassifier;
use App\Services\Marketing\MarketingApiHttpFactory;
use App\Services\Marketing\MarketingApiTraceRecorder;
use App\Services\Marketing\NotificationDispatchFailureResolver;
use App\Services\Marketing\NotificationDispatchLogData;
use App\Services\Marketing\NotificationDispatchLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMassNotificationCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    private const int MaxAttempts = 6;

    public int $timeout;

    /**
     * @param  list<string>  $emails
     */
    public function __construct(
        public int $massNotificationId,
        public array $emails,
        public string $subject,
        public string $copy,
        public int $sentById,
        public string $source,
        public ?string $dispatchRunId = null,
        public int $attempt = 1,
    ) {
        $this->onQueue('email');
        $this->timeout = (int) config('services.marketing_api.email_timeout', 300) + 60;
    }

    public function handle(
        NotificationDispatchLogger $dispatchLogger,
        MarketingApiTraceRecorder $apiTraceRecorder,
        DispatchProgressTracker $progressTracker,
        EmailDispatchFailureClassifier $classifier,
    ): void {
        $notification = MassNotification::query()->find($this->massNotificationId);

        if ($notification === null) {
            return;
        }

        $source = NotificationDispatchSource::tryFrom($this->source) ?? NotificationDispatchSource::MassAudience;

        if ($this->dispatchRunId !== null) {
            $progressTracker->markBatchProcessing(
                runId: $this->dispatchRunId,
                channelLabel: BirthdayNotificationChannel::Email->getLabel(),
                batchNumber: 1,
                totalBatches: 1,
                recipientCount: count($this->emails),
                detail: 'Creando campaña en Mailchimp…',
            );
        }

        $endpoint = $this->campaignsEndpoint();
        $payload = [
            'recipients' => array_values($this->emails),
            'copy' => $this->copy,
            'subject' => $this->subject,
            'title' => $this->subject,
        ];

        try {
            $response = app(MarketingApiHttpFactory::class)
                ->emailClient()
                ->asJson()
                ->post($endpoint, $payload);

            $apiTrace = $apiTraceRecorder->fromResponse(
                label: 'Campaña Mailchimp (notificación masiva)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            /** @var array{sent?: int, total?: int, success?: bool, campaign_id?: string, synced?: int, message?: string, reason?: string, error?: string, failures?: list<array<string, mixed>>}|null $responsePayload */
            $responsePayload = $response->json();
            $body = is_array($responsePayload) ? $responsePayload : [];
            $campaignId = filled($body['campaign_id'] ?? null) ? (string) $body['campaign_id'] : null;
            $sent = (int) ($body['sent'] ?? ($response->successful() ? count($this->emails) : 0));
            $total = (int) ($body['total'] ?? count($this->emails));
            $payloadSuccess = ($body['success'] ?? null) !== false;
            $successful = $response->successful() && $payloadSuccess && $sent > 0;

            $message = $successful
                ? (string) ($body['message'] ?? 'Mailchimp aceptó la campaña.')
                : $this->resolveApiErrorMessage($body, $response->body());

            if ($campaignId !== null) {
                $apiTrace['mailchimp_campaign_id'] = $campaignId;
            }

            if (! $successful && $campaignId === null) {
                $handled = $this->handleFailure(
                    body: $body,
                    fallbackMessage: $message,
                    httpStatus: $response->status(),
                    apiTrace: $apiTrace,
                    source: $source,
                    dispatchLogger: $dispatchLogger,
                    progressTracker: $progressTracker,
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
                    batchNumber: 1,
                    totalBatches: 1,
                    recipientFailures: $this->extractRecipientFailures($body),
                );
            }

            if ($successful && $campaignId !== null) {
                SyncMailchimpCampaignReportJob::dispatch(
                    massNotificationId: $this->massNotificationId,
                    campaignId: $campaignId,
                )->delay(now()->addMinutes(5));
            }

            Log::info('Mass notification Mailchimp campaign queued.', [
                'mass_notification_id' => $this->massNotificationId,
                'campaign_id' => $campaignId,
                'attempt' => $this->attempt,
                'recipients' => count($this->emails),
                'sent' => $sent,
                'successful' => $successful,
            ]);
        } catch (ConnectionException $exception) {
            $isTimeout = $apiTraceRecorder->isTimeout($exception);
            $message = $isTimeout
                ? 'El API de correos no confirmó el resultado a tiempo. Es probable que la campaña sí se haya enviado.'
                : 'No se pudo conectar con el API de correos.';

            $apiTrace = $apiTraceRecorder->fromConnectionError(
                label: 'Campaña Mailchimp (notificación masiva)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                exception: $exception,
            );

            if (! $isTimeout && $this->canRetryAgain()) {
                $this->reschedule(
                    kind: EmailDispatchFailureKind::Transient,
                    reason: $message,
                    apiTrace: $apiTrace,
                    source: $source,
                    dispatchLogger: $dispatchLogger,
                    progressTracker: $progressTracker,
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
                    batchNumber: 1,
                    totalBatches: 1,
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
            detail: $exception?->getMessage() ?? 'Error inesperado al crear la campaña de Mailchimp.',
            batchNumber: 1,
            totalBatches: 1,
        );
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, mixed>  $apiTrace
     */
    private function handleFailure(
        array $body,
        string $fallbackMessage,
        int $httpStatus,
        array $apiTrace,
        NotificationDispatchSource $source,
        NotificationDispatchLogger $dispatchLogger,
        DispatchProgressTracker $progressTracker,
        EmailDispatchFailureClassifier $classifier,
    ): bool {
        $diagnosis = $classifier->messageFromResponseBody($body, $fallbackMessage);
        $kind = $classifier->classify($httpStatus, $diagnosis);

        if (! $kind->isRetryable() || ! $this->canRetryAgain()) {
            return false;
        }

        $this->reschedule(
            kind: $kind,
            reason: $diagnosis,
            apiTrace: $apiTrace,
            source: $source,
            dispatchLogger: $dispatchLogger,
            progressTracker: $progressTracker,
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $apiTrace
     */
    private function reschedule(
        EmailDispatchFailureKind $kind,
        string $reason,
        array $apiTrace,
        NotificationDispatchSource $source,
        NotificationDispatchLogger $dispatchLogger,
        DispatchProgressTracker $progressTracker,
    ): void {
        $delaySeconds = $kind->cooldownSeconds($this->attempt);
        $retryAt = now()->addSeconds($delaySeconds);
        $nextAttempt = $this->attempt + 1;

        $message = $kind->label().'. Campaña reencolada para las '.$retryAt->format('H:i')
            .' (intento '.$nextAttempt.' de '.self::MaxAttempts.'). No relances el envío: Mailchimp podría duplicar la campaña.';

        self::dispatch(
            massNotificationId: $this->massNotificationId,
            emails: $this->emails,
            subject: $this->subject,
            copy: $this->copy,
            sentById: $this->sentById,
            source: $this->source,
            dispatchRunId: $this->dispatchRunId,
            attempt: $nextAttempt,
        )->delay($retryAt);

        $dispatchLogger->logChannelResult(
            base: $this->logBase($source, $message, $apiTrace),
            successful: false,
            message: $message,
            sent: 0,
            total: count($this->emails),
        );

        if ($this->dispatchRunId !== null) {
            $progressTracker->markJobQueued($this->dispatchRunId, $message);
        }

        Log::warning('Mass notification Mailchimp campaign rescheduled.', [
            'mass_notification_id' => $this->massNotificationId,
            'kind' => $kind->value,
            'attempt' => $this->attempt,
            'next_attempt' => $nextAttempt,
            'retry_at' => $retryAt->toIso8601String(),
            'reason' => $reason,
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
            batchNumber: 1,
            totalBatches: 1,
            technicalDetail: $apiTrace,
        );
    }

    private function campaignsEndpoint(): string
    {
        $path = config('services.marketing_api.mass_email_campaigns_path', '/api/emails/campaigns');

        return rtrim((string) config('services.marketing_api.base_url'), '/').$path;
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
