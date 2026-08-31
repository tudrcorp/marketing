<?php

namespace App\Services\Marketing;

use App\Jobs\ProcessMassNotificationChannelsJob;
use App\Jobs\SendMassNotificationEmailBatchJob;
use App\Jobs\SendMassNotificationWhatsAppBatchJob;
use App\Mail\MassNotificationEmailAttachments;
use App\Mail\MassNotificationEmailRenderer;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MassNotificationContentType;
use App\Marketing\MassNotificationRecipient;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MassNotificationDispatchService
{
    public function __construct(
        private MassNotificationRecipientResolver $recipientResolver,
        private MarketingAudienceContactCollector $audienceContactCollector,
        private NotificationDispatchLogger $dispatchLogger,
        private MarketingApiTraceRecorder $apiTraceRecorder,
        private DispatchProgressTracker $progressTracker,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $selectedRecords
     * @param  array{
     *     title: string,
     *     copy: string,
     *     channels: list<string>,
     *     content_type?: string|MassNotificationContentType|null,
     *     attachment?: string|null,
     * }  $data
     */
    public function dispatch(
        BirthdayNotificationAudience $audience,
        Collection $selectedRecords,
        array $data,
        User $sentBy,
    ): MassNotificationDispatchResult {
        $recipients = $this->recipientResolver->resolveMany(
            $audience,
            $selectedRecords->values()->all(),
        );

        $notification = MassNotification::query()->create([
            'title' => $data['title'],
            'copy' => $data['copy'],
            'channels' => $this->normalizeChannels($data['channels'] ?? []),
            'audiences' => [$audience->value],
            'recipient_ids' => collect($recipients)->pluck('sourceId')->values()->all(),
            'content_type' => $this->normalizeContentType($data['content_type'] ?? null),
            'attachment' => $data['attachment'] ?? null,
            'created_by_id' => $sentBy->getKey(),
        ]);

        $dispatchRunId = $this->startProgressRun(
            sentBy: $sentBy,
            notification: $notification,
            recipientCount: count($recipients),
        );

        if ($dispatchRunId !== null) {
            $this->queueChannelProcessing(
                notification: $notification,
                recipients: $recipients,
                sentBy: $sentBy,
                source: NotificationDispatchSource::MassIndividual,
                dispatchRunId: $dispatchRunId,
            );

            return new MassNotificationDispatchResult(
                notification: $notification,
                channelResults: [],
                dispatchRunId: $dispatchRunId,
                queued: true,
            );
        }

        $channelResults = $this->processChannels(
            notification: $notification,
            recipients: $recipients,
            sentBy: $sentBy,
            source: NotificationDispatchSource::MassIndividual,
        );

        return new MassNotificationDispatchResult($notification, $channelResults, $dispatchRunId);
    }

    /**
     * @param  list<string>  $audiences
     * @param  array{
     *     title: string,
     *     copy: string,
     *     channels: list<string>,
     *     content_type?: string|MassNotificationContentType|null,
     *     attachment?: string|null,
     * }  $data
     */
    public function dispatchToAudiences(
        array $audiences,
        array $data,
        User $sentBy,
        NotificationDispatchSource $source = NotificationDispatchSource::MassAudience,
        ?int $corporateEventId = null,
    ): MassNotificationDispatchResult {
        $audienceEnums = collect($audiences)
            ->map(fn (string $audience): ?BirthdayNotificationAudience => BirthdayNotificationAudience::tryFrom($audience))
            ->filter()
            ->values()
            ->all();

        $recipients = $this->audienceContactCollector->collect($audienceEnums);

        $notification = MassNotification::query()->create([
            'title' => $data['title'],
            'copy' => $data['copy'],
            'channels' => $this->normalizeChannels($data['channels'] ?? []),
            'audiences' => collect($audiences)->values()->all(),
            'recipient_ids' => collect($recipients)->pluck('sourceId')->values()->all(),
            'content_type' => $this->normalizeContentType($data['content_type'] ?? null),
            'attachment' => $data['attachment'] ?? null,
            'created_by_id' => $sentBy->getKey(),
        ]);

        $dispatchRunId = $this->startProgressRun(
            sentBy: $sentBy,
            notification: $notification,
            recipientCount: count($recipients),
        );

        if ($dispatchRunId !== null) {
            $this->queueChannelProcessing(
                notification: $notification,
                recipients: $recipients,
                sentBy: $sentBy,
                source: $source,
                dispatchRunId: $dispatchRunId,
                corporateEventId: $corporateEventId,
            );

            return new MassNotificationDispatchResult(
                notification: $notification,
                channelResults: [],
                dispatchRunId: $dispatchRunId,
                queued: true,
            );
        }

        $channelResults = $this->processChannels(
            notification: $notification,
            recipients: $recipients,
            sentBy: $sentBy,
            source: $source,
            corporateEventId: $corporateEventId,
        );

        return new MassNotificationDispatchResult($notification, $channelResults, $dispatchRunId);
    }

    public function dispatchExisting(
        MassNotification $notification,
        User $sentBy,
    ): MassNotificationDispatchResult {
        $recipients = $this->resolveRecipientsForNotification($notification);
        $source = filled($notification->recipient_ids)
            ? NotificationDispatchSource::MassIndividual
            : NotificationDispatchSource::MassAudience;

        if ($recipients === []) {
            $emptyResult = new MassNotificationChannelResult(
                channel: $notification->channelEnums()[0] ?? BirthdayNotificationChannel::Email,
                successful: false,
                sent: 0,
                total: 0,
                message: 'No se encontraron destinatarios válidos para esta notificación. Revisa los grupos o IDs configurados.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: sin destinatarios resolubles para la campaña.'),
            );

            $this->dispatchLogger->logChannelResult(
                base: new NotificationDispatchLogData(
                    source: $source,
                    status: NotificationDispatchStatus::Failed,
                    title: $notification->title,
                    summary: $emptyResult->message,
                    channel: $emptyResult->channel,
                    massNotificationId: $notification->getKey(),
                    sentById: $sentBy->getKey(),
                    technicalDetail: $emptyResult->apiTrace,
                ),
                successful: false,
                message: $emptyResult->message,
            );

            return new MassNotificationDispatchResult(
                notification: $notification,
                channelResults: [$emptyResult],
            );
        }

        $dispatchRunId = $this->startProgressRun(
            sentBy: $sentBy,
            notification: $notification,
            recipientCount: count($recipients),
        );

        if ($dispatchRunId !== null) {
            $this->queueChannelProcessing(
                notification: $notification,
                recipients: $recipients,
                sentBy: $sentBy,
                source: $source,
                dispatchRunId: $dispatchRunId,
            );

            return new MassNotificationDispatchResult(
                notification: $notification,
                channelResults: [],
                dispatchRunId: $dispatchRunId,
                queued: true,
            );
        }

        $channelResults = $this->processChannels(
            notification: $notification,
            recipients: $recipients,
            sentBy: $sentBy,
            source: $source,
        );

        return new MassNotificationDispatchResult($notification, $channelResults, $dispatchRunId);
    }

    /**
     * @return list<MassNotificationRecipient>
     */
    public function resolveRecipientsForNotification(MassNotification $notification): array
    {
        $audiences = $notification->audienceEnums();

        if ($audiences === []) {
            return [];
        }

        $recipients = $this->audienceContactCollector->collect($audiences);
        $recipientIds = collect($notification->recipient_ids ?? [])
            ->map(fn (mixed $id): string => (string) $id)
            ->filter()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return $recipients;
        }

        return collect($recipients)
            ->filter(fn (MassNotificationRecipient $recipient): bool => in_array($recipient->sourceId, $recipientIds, true))
            ->values()
            ->all();
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     * @return list<MassNotificationChannelResult>
     */
    public function processChannels(
        MassNotification $notification,
        array $recipients,
        User $sentBy,
        NotificationDispatchSource $source,
        ?int $corporateEventId = null,
        ?string $dispatchRunId = null,
    ): array {
        return collect($notification->channelEnums())
            ->map(fn (BirthdayNotificationChannel $channel): MassNotificationChannelResult => $this->dispatchChannel(
                notification: $notification,
                channel: $channel,
                recipients: $recipients,
                sentBy: $sentBy,
                source: $source,
                corporateEventId: $corporateEventId,
                dispatchRunId: $dispatchRunId,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     */
    private function queueChannelProcessing(
        MassNotification $notification,
        array $recipients,
        User $sentBy,
        NotificationDispatchSource $source,
        string $dispatchRunId,
        ?int $corporateEventId = null,
    ): void {
        $this->progressTracker->markJobQueued(
            runId: $dispatchRunId,
            detail: 'Preparando envío… El avance se actualizará en tiempo real.',
        );

        $pendingDispatch = ProcessMassNotificationChannelsJob::dispatch(
            massNotificationId: $notification->getKey(),
            recipients: $this->serializeRecipients($recipients),
            sentById: $sentBy->getKey(),
            source: $source->value,
            dispatchRunId: $dispatchRunId,
            corporateEventId: $corporateEventId,
        )->onConnection('sync');

        // En la UI, devolver la respuesta de Livewire primero para mostrar el floater
        // y recién después ejecutar el envío (SMTP/colas internas).
        if ($this->shouldDispatchAfterResponse()) {
            $pendingDispatch->afterResponse();
        }
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     * @return list<array{
     *     sourceId: string,
     *     name: string,
     *     audience: string,
     *     email: ?string,
     *     phone: ?string,
     *     replyPhone: ?string,
     *     replyContactName: ?string
     * }>
     */
    private function serializeRecipients(array $recipients): array
    {
        return collect($recipients)
            ->map(fn (MassNotificationRecipient $recipient): array => [
                'sourceId' => $recipient->sourceId,
                'name' => $recipient->name,
                'audience' => $recipient->audience->value,
                'email' => $recipient->email,
                'phone' => $recipient->phone,
                'replyPhone' => $recipient->replyPhone,
                'replyContactName' => $recipient->replyContactName,
            ])
            ->values()
            ->all();
    }

    private function shouldDispatchAfterResponse(): bool
    {
        return ! app()->runningUnitTests();
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     */
    private function dispatchChannel(
        MassNotification $notification,
        BirthdayNotificationChannel $channel,
        array $recipients,
        User $sentBy,
        NotificationDispatchSource $source,
        ?int $corporateEventId = null,
        ?string $dispatchRunId = null,
    ): MassNotificationChannelResult {
        try {
            $result = match ($channel) {
                BirthdayNotificationChannel::Email => $this->dispatchEmail(
                    $notification,
                    $recipients,
                    $sentBy,
                    $source,
                    $dispatchRunId,
                ),
                BirthdayNotificationChannel::WhatsApp,
                BirthdayNotificationChannel::Sms => $this->dispatchPhoneChannel(
                    $notification,
                    $channel,
                    $recipients,
                    $sentBy,
                    $dispatchRunId,
                ),
            };
        } catch (Throwable $exception) {
            $result = new MassNotificationChannelResult(
                channel: $channel,
                successful: false,
                sent: 0,
                total: count($recipients),
                message: $exception->getMessage(),
            );
        }

        $this->dispatchLogger->logChannelResult(
            base: new NotificationDispatchLogData(
                source: $source,
                status: NotificationDispatchStatus::Failed,
                title: $notification->title,
                summary: $result->message,
                channel: $channel,
                massNotificationId: $notification->getKey(),
                corporateEventId: $corporateEventId,
                sentById: $sentBy->getKey(),
                technicalDetail: $result->apiTrace,
            ),
            successful: $result->successful,
            message: $result->message,
            sent: $result->sent,
            total: $result->total,
        );

        return $result;
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     */
    private function dispatchEmail(
        MassNotification $notification,
        array $recipients,
        User $sentBy,
        NotificationDispatchSource $source,
        ?string $dispatchRunId = null,
    ): MassNotificationChannelResult {
        $usesClientGroupRouting = $this->usesClientGroupRouting($recipients);

        $emails = collect($recipients)
            ->map(fn (MassNotificationRecipient $recipient): ?string => $recipient->email)
            ->filter()
            ->when(! $usesClientGroupRouting, fn ($collection) => $collection->unique())
            ->values()
            ->all();

        if ($emails === []) {
            return new MassNotificationChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                sent: 0,
                total: count($recipients),
                message: 'Ningún destinatario seleccionado tiene correo electrónico válido.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: ningún destinatario tiene correo válido.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->attachment)) {
            return new MassNotificationChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                sent: 0,
                total: count($emails),
                message: 'La notificación debe tener copy o adjunto para enviar el correo.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la campaña no tiene copy ni adjunto.'),
            );
        }

        // Con un run de progreso activo (más de un destinatario), se envía en lotes
        // pequeños por job en cola en vez de una sola llamada gigante: una audiencia
        // grande en una sola petición puede tardar más que el timeout HTTP (el propio
        // API externo también aborta requests largas del lado del servidor).
        if ($dispatchRunId !== null) {
            return $this->dispatchEmailInBatches($notification, $emails, $sentBy, $source, $dispatchRunId);
        }

        $endpoint = $this->bulkEmailsEndpoint();
        $payload = [
            'recipients' => json_encode($emails),
            'copy' => app(MassNotificationEmailRenderer::class)->render(
                notification: $notification,
                sentByName: $sentBy->name,
            ),
            'subject' => $notification->title,
        ];

        try {
            $response = MassNotificationEmailAttachments::attach(
                app(MarketingApiHttpFactory::class)->emailClient(),
                $notification,
            )->post($endpoint, $payload);

            $apiTrace = $this->apiTraceRecorder->fromResponse(
                label: 'Correo masivo (notificación)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            /** @var array{sent?: int, total?: int, failed?: int, success?: bool, message?: string, reason?: string, error?: string, failures?: list<array<string, mixed>>}|null $responsePayload */
            $responsePayload = $response->json();
            $payload = is_array($responsePayload) ? $responsePayload : [];
            $sent = (int) ($payload['sent'] ?? ($response->successful() ? count($emails) : 0));
            $total = (int) ($payload['total'] ?? count($emails));
            $payloadSuccess = ($payload['success'] ?? null) !== false;
            $channelSuccessful = $response->successful() && $payloadSuccess && $sent >= $total;

            if (! $response->successful() || ! $payloadSuccess) {
                return new MassNotificationChannelResult(
                    channel: BirthdayNotificationChannel::Email,
                    successful: $channelSuccessful || ($response->successful() && $sent > 0),
                    sent: $sent,
                    total: $total,
                    message: $this->resolveApiErrorMessage($payload, $response->body()),
                    apiTrace: $apiTrace,
                );
            }

            $failedFromPayload = max(0, $total - $sent);
            $message = (string) ($payload['message'] ?? 'Correos enviados correctamente.');

            if ($failedFromPayload > 0) {
                $message = "Envío parcial: {$sent} de {$total} correos entregados. {$failedFromPayload} fallido"
                    .($failedFromPayload === 1 ? '' : 's').'.';
            }

            return new MassNotificationChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: true,
                sent: $sent,
                total: $total,
                message: $message,
                apiTrace: $apiTrace,
            );
        } catch (ConnectionException $exception) {
            return new MassNotificationChannelResult(
                channel: BirthdayNotificationChannel::Email,
                successful: false,
                sent: 0,
                total: count($emails),
                message: $this->apiTraceRecorder->isTimeout($exception)
                    ? 'El API de correos no confirmó el resultado a tiempo. Es probable que los correos sí se hayan enviado.'
                    : 'No se pudo conectar con el API de correos.',
                apiTrace: $this->apiTraceRecorder->fromConnectionError(
                    label: 'Correo masivo (notificación)',
                    endpoint: $endpoint,
                    method: 'POST',
                    request: $payload,
                    exception: $exception,
                ),
            );
        }
    }

    /**
     * @param  list<string>  $emails
     */
    private function dispatchEmailInBatches(
        MassNotification $notification,
        array $emails,
        User $sentBy,
        NotificationDispatchSource $source,
        string $dispatchRunId,
    ): MassNotificationChannelResult {
        $batchSize = max(1, (int) config('services.marketing_api.mass_email_batch_size', 50));
        $batches = array_chunk($emails, $batchSize);
        $totalBatches = count($batches);
        $copy = app(MassNotificationEmailRenderer::class)->render(
            notification: $notification,
            sentByName: $sentBy->name,
        );

        $this->progressTracker->registerChannelUnits(
            runId: $dispatchRunId,
            channelLabel: BirthdayNotificationChannel::Email->getLabel(),
            units: $totalBatches,
            detail: "Encolando {$totalBatches} lote(s) de correo…",
        );

        // Los lotes se escalonan en vez de salir todos de golpe: una ráfaga contra el
        // proveedor SMTP dispara sus límites de conexión y de cuota (ver el 550 5.4.5 /
        // 454 que tumbó una campaña completa). Es el equivalente de la pausa que ya
        // aplica WhatsApp entre lotes.
        $pauseSeconds = max(0, (int) config('services.marketing_api.mass_email_batch_pause_seconds', 5));

        foreach ($batches as $index => $batchEmails) {
            $pendingBatch = SendMassNotificationEmailBatchJob::dispatch(
                massNotificationId: $notification->getKey(),
                emails: $batchEmails,
                subject: $notification->title,
                copy: $copy,
                batchNumber: $index + 1,
                totalBatches: $totalBatches,
                sentById: $sentBy->getKey(),
                source: $source->value,
                dispatchRunId: $dispatchRunId,
            );

            if ($pauseSeconds > 0 && $index > 0) {
                $pendingBatch->delay(now()->addSeconds($index * $pauseSeconds));
            }
        }

        $recipientCount = count($emails);

        return new MassNotificationChannelResult(
            channel: BirthdayNotificationChannel::Email,
            successful: true,
            sent: 0,
            total: $recipientCount,
            message: "Se encolaron {$totalBatches} lote(s) de correo para {$recipientCount} destinatario".($recipientCount === 1 ? '' : 's').'.',
            apiTrace: [
                'api_calls' => [],
                'notes' => 'El envío de correo se procesará por lotes en segundo plano. La respuesta del API se registrará en cada lote.',
                'queued_batches' => $totalBatches,
                'batch_size' => $batchSize,
            ],
        );
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     */
    private function dispatchPhoneChannel(
        MassNotification $notification,
        BirthdayNotificationChannel $channel,
        array $recipients,
        User $sentBy,
        ?string $dispatchRunId = null,
    ): MassNotificationChannelResult {
        if ($channel === BirthdayNotificationChannel::Sms) {
            return new MassNotificationChannelResult(
                channel: $channel,
                successful: false,
                sent: 0,
                total: count($recipients),
                message: 'El canal SMS aún no está configurado. Usa WhatsApp.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: canal SMS no habilitado en este entorno.'),
            );
        }

        $usesClientGroupRouting = $this->usesClientGroupRouting($recipients);

        $phones = collect($recipients)
            ->map(fn (MassNotificationRecipient $recipient): ?string => MarketingPhoneNormalizer::tryNormalize($recipient->phone))
            ->filter()
            ->when(! $usesClientGroupRouting, fn ($collection) => $collection->unique())
            ->values()
            ->all();

        if ($phones === []) {
            return new MassNotificationChannelResult(
                channel: $channel,
                successful: false,
                sent: 0,
                total: count($recipients),
                message: 'Ningún destinatario seleccionado tiene teléfono válido.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: ningún destinatario tiene teléfono válido.'),
            );
        }

        if (! filled($notification->copy) && ! filled($notification->attachment)) {
            return new MassNotificationChannelResult(
                channel: $channel,
                successful: false,
                sent: 0,
                total: count($phones),
                message: 'La notificación debe tener copy o adjunto para enviar el mensaje.',
                apiTrace: $this->apiTraceRecorder->fromLocalValidation('Validación local: la campaña no tiene copy ni adjunto.'),
            );
        }

        $batchSize = max(1, (int) config('services.marketing_api.whatsapp_batch_size', 50));
        $batchPauseSeconds = max(0, (int) config('services.marketing_api.whatsapp_batch_pause_seconds', 15));
        $batches = array_chunk($phones, $batchSize);
        $totalBatches = count($batches);
        $attachmentUrl = $this->attachmentUrl($notification);
        $attachmentBase64 = $this->attachmentBase64($notification);
        $copy = $this->copyForPhoneChannel($notification->copy, $recipients, $usesClientGroupRouting);

        if ($dispatchRunId !== null) {
            $this->progressTracker->registerChannelUnits(
                runId: $dispatchRunId,
                channelLabel: $channel->getLabel(),
                units: $totalBatches,
                detail: "Encolando {$totalBatches} lote(s)…",
            );
        }

        if ($dispatchRunId !== null) {
            $this->progressTracker->markJobQueued(
                runId: $dispatchRunId,
                detail: "Se encolaron {$totalBatches} lote(s) de WhatsApp. Procesando en segundo plano…",
            );
        }

        foreach ($batches as $index => $batchPhones) {
            SendMassNotificationWhatsAppBatchJob::dispatch(
                massNotificationId: $notification->getKey(),
                phones: $batchPhones,
                title: $notification->title,
                copy: $copy,
                attachmentUrl: $attachmentUrl,
                batchNumber: $index + 1,
                totalBatches: $totalBatches,
                sentById: $sentBy->getKey(),
                channel: $channel,
                attachmentBase64: $attachmentBase64,
                dispatchRunId: $dispatchRunId,
            )->delay(now()->addSeconds($index * $batchPauseSeconds));
        }

        $recipientCount = count($recipients);

        return new MassNotificationChannelResult(
            channel: $channel,
            successful: true,
            sent: count($phones),
            total: $recipientCount,
            message: "Se encolaron {$totalBatches} lote(s) de WhatsApp para ".count($phones).' envío'.(count($phones) === 1 ? '' : 's').' a '.$recipientCount.' cliente'.($recipientCount === 1 ? '' : 's').'.',
            apiTrace: [
                'api_calls' => [],
                'notes' => 'El envío a WhatsApp se procesará por lotes en segundo plano. La respuesta del API se registrará en cada lote.',
                'queued_batches' => $totalBatches,
                'batch_size' => $batchSize,
                'attachment_url' => $attachmentUrl,
            ],
        );
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     */
    private function copyForPhoneChannel(string $copy, array $recipients, bool $usesClientGroupRouting): string
    {
        if (! $usesClientGroupRouting) {
            return $copy;
        }

        $footers = collect($recipients)
            ->filter(fn (MassNotificationRecipient $recipient): bool => filled($recipient->replyPhone))
            ->unique(fn (MassNotificationRecipient $recipient): string => $recipient->replyPhone.'|'.$recipient->replyContactName)
            ->map(fn (MassNotificationRecipient $recipient): string => 'Contacto responsable: '
                .($recipient->replyContactName ?? 'Equipo TDG')
                .' ('.$recipient->replyPhone.')')
            ->values()
            ->all();

        if ($footers === []) {
            return $copy;
        }

        return trim($copy)."\n\n—\n".implode("\n", $footers);
    }

    private function attachmentUrl(MassNotification $notification): ?string
    {
        if (! filled($notification->attachment)) {
            return null;
        }

        if (! Storage::disk('public')->exists($notification->attachment)) {
            return null;
        }

        $url = Storage::disk('public')->url($notification->attachment);

        if (app()->environment('local') && str_contains($url, '.test')) {
            return str_replace('https://', 'http://', $url);
        }

        return $url;
    }

    private function attachmentBase64(MassNotification $notification): ?string
    {
        if (! filled($notification->attachment)) {
            return null;
        }

        if (! Storage::disk('public')->exists($notification->attachment)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($notification->attachment) ?? 'image/jpeg';

        return 'data:'.$mimeType.';base64,'.base64_encode(
            Storage::disk('public')->get($notification->attachment),
        );
    }

    /**
     * @param  list<string|BirthdayNotificationChannel>  $channels
     * @return list<string>
     */
    private function normalizeChannels(array $channels): array
    {
        return collect($channels)
            ->map(fn (string|BirthdayNotificationChannel $channel): string => $channel instanceof BirthdayNotificationChannel
                ? $channel->value
                : $channel)
            ->values()
            ->all();
    }

    private function normalizeContentType(mixed $contentType): ?string
    {
        return MassNotificationContentType::tryFromMixed($contentType)?->value;
    }

    private function bulkEmailsEndpoint(): string
    {
        $path = config('services.marketing_api.bulk_emails_path', '/api/emails/bulk');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveApiErrorMessage(?array $payload, string $body): string
    {
        if (is_array($payload)) {
            foreach (['reason', 'message', 'error', 'detail'] as $key) {
                if (filled($payload[$key] ?? null)) {
                    return (string) $payload[$key];
                }
            }
        }

        return filled($body) ? $body : 'El API rechazó el envío.';
    }

    /**
     * @param  list<MassNotificationRecipient>  $recipients
     */
    private function usesClientGroupRouting(array $recipients): bool
    {
        return collect($recipients)
            ->contains(fn (MassNotificationRecipient $recipient): bool => $recipient->audience === BirthdayNotificationAudience::ClientGroups);
    }

    private function startProgressRun(User $sentBy, MassNotification $notification, int $recipientCount): ?string
    {
        if ($recipientCount <= 1) {
            return null;
        }

        return $this->progressTracker->start(
            userId: $sentBy->getKey(),
            massNotificationId: $notification->getKey(),
            title: $notification->title,
            totalRecipients: $recipientCount,
        );
    }

}
