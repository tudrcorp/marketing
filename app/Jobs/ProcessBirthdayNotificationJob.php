<?php

namespace App\Jobs;

use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use App\Services\Marketing\BirthdayNotificationRecipientCollector;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessBirthdayNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $birthdayNotificationId,
        public ?string $referenceDate = null,
    ) {}

    public function handle(BirthdayNotificationRecipientCollector $collector): void
    {
        $notification = BirthdayNotification::query()->find($this->birthdayNotificationId);

        if ($notification === null) {
            Log::warning('Birthday notification dispatch skipped: notification not found.', [
                'notification_id' => $this->birthdayNotificationId,
            ]);

            return;
        }

        if (! $this->supportsEmailChannel($notification)) {
            Log::info('Birthday notification dispatch skipped: email channel not configured.', [
                'notification_id' => $notification->getKey(),
            ]);

            return;
        }

        $referenceDate = filled($this->referenceDate)
            ? Carbon::parse($this->referenceDate)->startOfDay()
            : Carbon::today();

        $recipients = $collector->collect($notification, $referenceDate);
        $totalRecipients = count($recipients);

        if ($totalRecipients === 0) {
            Log::info('Birthday notification dispatch completed with no recipients.', [
                'notification_id' => $notification->getKey(),
                'reference_date' => $referenceDate->toDateString(),
            ]);

            return;
        }

        $batchSize = $this->batchSize();
        $emails = collect($recipients)
            ->map(fn ($recipient): string => $recipient->email)
            ->values()
            ->all();
        $batches = array_chunk($emails, $batchSize);
        $totalBatches = count($batches);

        Log::info('Birthday notification dispatch queued.', [
            'notification_id' => $notification->getKey(),
            'notification_name' => $notification->name,
            'reference_date' => $referenceDate->toDateString(),
            'total_recipients' => $totalRecipients,
            'batch_size' => $batchSize,
            'total_batches' => $totalBatches,
        ]);

        foreach ($batches as $index => $batchRecipients) {
            SendBirthdayEmailBatchJob::dispatch(
                birthdayNotificationId: $notification->getKey(),
                recipients: $batchRecipients,
                batchNumber: $index + 1,
                totalBatches: $totalBatches,
            );
        }
    }

    private function supportsEmailChannel(BirthdayNotification $notification): bool
    {
        return in_array(
            BirthdayNotificationChannel::Email->value,
            $notification->channels ?? [],
            true,
        );
    }

    private function batchSize(): int
    {
        return max(1, (int) config('services.marketing_api.birthday_email_batch_size', 50));
    }
}
