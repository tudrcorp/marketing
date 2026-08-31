<?php

namespace App\Jobs;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\MarketingApiTraceRecorder;
use App\Services\Marketing\NotificationDispatchLogData;
use App\Services\Marketing\NotificationDispatchLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use App\Services\Marketing\MarketingApiHttpFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMassNotificationWhatsAppBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  list<string>  $phones
     */
    public function __construct(
        public int $massNotificationId,
        public array $phones,
        public string $title,
        public string $copy,
        public ?string $attachmentUrl,
        public int $batchNumber,
        public int $totalBatches,
        public int $sentById,
        public BirthdayNotificationChannel $channel = BirthdayNotificationChannel::WhatsApp,
        public ?string $attachmentBase64 = null,
        public ?string $dispatchRunId = null,
    ) {}

    public function handle(
        NotificationDispatchLogger $dispatchLogger,
        MarketingApiTraceRecorder $apiTraceRecorder,
        DispatchProgressTracker $progressTracker,
    ): void {
        if ($this->channel !== BirthdayNotificationChannel::WhatsApp) {
            return;
        }

        if ($this->dispatchRunId !== null) {
            $progressTracker->markBatchProcessing(
                runId: $this->dispatchRunId,
                channelLabel: $this->channel->getLabel(),
                batchNumber: $this->batchNumber,
                totalBatches: $this->totalBatches,
                recipientCount: count($this->phones),
            );
        }

        $endpoint = $this->batchEndpoint();
        $payload = [
            'channel' => $this->channel->value,
            'recipients' => array_values($this->phones),
            'title' => $this->title,
            'copy' => $this->copy,
            'attachment_url' => $this->attachmentUrl,
            'attachment_base64' => $this->attachmentBase64,
            'notification_id' => $this->massNotificationId,
            'batch_number' => $this->batchNumber,
            'total_batches' => $this->totalBatches,
            'sent_by_id' => $this->sentById,
        ];

        try {
            $response = app(MarketingApiHttpFactory::class)
                ->client($this->timeout())
                ->post($endpoint, $payload);

            $apiTrace = $apiTraceRecorder->fromResponse(
                label: 'WhatsApp masivo (lote)',
                endpoint: $endpoint,
                method: 'POST',
                request: $payload,
                response: $response,
            );

            if (! in_array($response->status(), [200, 202], true)) {
                $message = 'El API rechazó el lote de WhatsApp (HTTP '.$response->status().').';

                Log::error('WhatsApp batch rejected by marketing API.', [
                    'mass_notification_id' => $this->massNotificationId,
                    'batch' => $this->batchNumber,
                    'total_batches' => $this->totalBatches,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $dispatchLogger->logChannelResult(
                    base: new NotificationDispatchLogData(
                        source: NotificationDispatchSource::MassAudience,
                        status: NotificationDispatchStatus::Failed,
                        title: $this->title,
                        summary: $message,
                        channel: $this->channel,
                        massNotificationId: $this->massNotificationId,
                        sentById: $this->sentById,
                        batchNumber: $this->batchNumber,
                        totalBatches: $this->totalBatches,
                        technicalDetail: $apiTrace,
                    ),
                    successful: false,
                    message: $message,
                    sent: 0,
                    total: count($this->phones),
                );

                if ($this->dispatchRunId !== null) {
                    $progressTracker->recordUnitCompletion(
                        runId: $this->dispatchRunId,
                        channelLabel: $this->channel->getLabel(),
                        sent: 0,
                        failed: count($this->phones),
                        detail: $message,
                        batchNumber: $this->batchNumber,
                        totalBatches: $this->totalBatches,
                    );
                }

                $response->throw();
            }

            $queueStats = $this->extractQueueStats($response);
            $accepted = (bool) ($response->json('accepted') ?? $response->json('success'));
            $successful = $accepted;
            $summary = 'Lote de WhatsApp aceptado por el API. Ultramsg procesará los mensajes en segundo plano.';

            if ($queueStats['failed'] > 0) {
                $summary .= ' La cola global del API acumula '.$queueStats['failed'].' fallo(s); si el mensaje no llega, revisa los logs de integracorp-api ([whatsapp-queue]).';
            }

            $dispatchLogger->logChannelResult(
                base: new NotificationDispatchLogData(
                    source: NotificationDispatchSource::MassAudience,
                    status: $successful ? NotificationDispatchStatus::Sent : NotificationDispatchStatus::Failed,
                    title: $this->title,
                    summary: $summary,
                    channel: $this->channel,
                    massNotificationId: $this->massNotificationId,
                    sentById: $this->sentById,
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                    technicalDetail: $apiTrace,
                ),
                successful: $successful,
                message: $summary,
                sent: $successful ? count($this->phones) : 0,
                total: count($this->phones),
            );

            if ($this->dispatchRunId !== null) {
                $progressTracker->recordUnitCompletion(
                    runId: $this->dispatchRunId,
                    channelLabel: $this->channel->getLabel(),
                    sent: $successful ? count($this->phones) : 0,
                    failed: $successful ? 0 : count($this->phones),
                    detail: $successful ? 'Lote aceptado por el API' : 'El API no aceptó el lote de WhatsApp.',
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                );
            }

            Log::info('WhatsApp batch accepted by marketing API.', [
                'mass_notification_id' => $this->massNotificationId,
                'batch' => $this->batchNumber,
                'total_batches' => $this->totalBatches,
                'recipients' => count($this->phones),
                'queued' => $response->json('queued'),
                'queue' => $queueStats,
                'successful' => $successful,
            ]);
        } catch (ConnectionException $exception) {
            Log::error('WhatsApp batch could not reach marketing API.', [
                'mass_notification_id' => $this->massNotificationId,
                'batch' => $this->batchNumber,
                'message' => $exception->getMessage(),
            ]);

            $dispatchLogger->logChannelResult(
                base: new NotificationDispatchLogData(
                    source: NotificationDispatchSource::MassAudience,
                    status: NotificationDispatchStatus::Failed,
                    title: $this->title,
                    summary: 'No se pudo conectar con el API de mensajería.',
                    channel: $this->channel,
                    massNotificationId: $this->massNotificationId,
                    sentById: $this->sentById,
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                    technicalDetail: $apiTraceRecorder->fromConnectionError(
                        label: 'WhatsApp masivo (lote)',
                        endpoint: $endpoint,
                        method: 'POST',
                        request: $payload,
                        exception: $exception,
                    ),
                ),
                successful: false,
                message: 'No se pudo conectar con el API de mensajería.',
                sent: 0,
                total: count($this->phones),
            );

            if ($this->dispatchRunId !== null) {
                $progressTracker->markBatchError(
                    runId: $this->dispatchRunId,
                    channelLabel: $this->channel->getLabel(),
                    technicalDetail: 'No se pudo conectar con el API de mensajería.',
                    batchNumber: $this->batchNumber,
                    totalBatches: $this->totalBatches,
                );
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->dispatchRunId === null) {
            return;
        }

        $technicalDetail = filled($exception?->getMessage())
            ? $exception->getMessage()
            : 'Error inesperado al procesar el lote de WhatsApp.';

        if ($exception instanceof ConnectionException) {
            $technicalDetail = 'No se pudo conectar con el API de mensajería.';
        }

        app(DispatchProgressTracker::class)->recordUnitCompletion(
            runId: $this->dispatchRunId,
            channelLabel: $this->channel->getLabel(),
            sent: 0,
            failed: count($this->phones),
            detail: $technicalDetail,
            batchNumber: $this->batchNumber,
            totalBatches: $this->totalBatches,
        );
    }

    private function batchEndpoint(): string
    {
        $path = config('services.marketing_api.mass_send_batch_path', '/api/notifications/mass/send-batch');

        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    private function timeout(): int
    {
        return max(5, (int) config('services.marketing_api.batch_timeout', 30));
    }

    /**
     * @return array{pending: int, processing: bool, processed: int, failed: int}
     */
    private function extractQueueStats(\Illuminate\Http\Client\Response $response): array
    {
        /** @var array<string, mixed>|null $queue */
        $queue = $response->json('queue');

        if (! is_array($queue)) {
            return [
                'pending' => 0,
                'processing' => false,
                'processed' => 0,
                'failed' => 0,
            ];
        }

        return [
            'pending' => (int) ($queue['pending'] ?? 0),
            'processing' => (bool) ($queue['processing'] ?? false),
            'processed' => (int) ($queue['processed'] ?? 0),
            'failed' => (int) ($queue['failed'] ?? 0),
        ];
    }
}
