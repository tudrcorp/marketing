<?php

namespace App\Jobs;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\BirthdayNotification;
use App\Services\Marketing\BirthdayNotificationBulkEmailService;
use App\Services\Marketing\NotificationDispatchLogData;
use App\Services\Marketing\NotificationDispatchLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBirthdayEmailBatchJob implements ShouldQueue
{
    use Queueable;

    /**
     * El POST al endpoint bulk espera hasta `services.marketing_api.email_timeout` segundos
     * porque el API entrega los correos por SMTP antes de responder. Sin este timeout el
     * worker aplica su valor por defecto (60 s) y mata el job a media entrega: los correos
     * ya salieron del lado del API, pero aquí no queda traza, ni log de despacho, ni
     * reprogramación de los pendientes.
     */
    public int $timeout;

    /**
     * @param  list<string>  $recipients
     */
    public function __construct(
        public int $birthdayNotificationId,
        public array $recipients,
        public int $batchNumber,
        public int $totalBatches,
    ) {
        $this->onQueue('email');
        $this->timeout = (int) config('services.marketing_api.email_timeout', 300) + 60;
    }

    public function handle(
        BirthdayNotificationBulkEmailService $bulkEmailService,
        NotificationDispatchLogger $dispatchLogger,
    ): void {
        $notification = BirthdayNotification::query()->find($this->birthdayNotificationId);

        if ($notification === null) {
            Log::warning('Birthday email batch skipped: notification not found.', [
                'notification_id' => $this->birthdayNotificationId,
                'batch' => $this->batchNumber,
            ]);

            return;
        }

        $result = $bulkEmailService->sendBatch(
            notification: $notification,
            recipients: $this->recipients,
        );

        $dispatchLogger->logChannelResult(
            base: new NotificationDispatchLogData(
                source: NotificationDispatchSource::BirthdayBulk,
                status: NotificationDispatchStatus::Failed,
                title: $notification->name,
                summary: $result->message,
                channel: BirthdayNotificationChannel::Email,
                birthdayNotificationId: $notification->getKey(),
                sentById: $notification->created_by_id,
                batchNumber: $this->batchNumber,
                totalBatches: $this->totalBatches,
                technicalDetail: $result->apiTrace,
            ),
            successful: $result->successful,
            message: $result->message,
            sent: $result->sent,
            total: $result->total,
        );

        Log::info('Birthday email batch processed.', [
            'notification_id' => $notification->getKey(),
            'notification_name' => $notification->name,
            'batch' => $this->batchNumber,
            'total_batches' => $this->totalBatches,
            'recipients' => count($this->recipients),
            'successful' => $result->successful,
            'sent' => $result->sent,
            'message' => $result->message,
        ]);
    }
}
