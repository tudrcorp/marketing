<?php

namespace App\Jobs;

use App\Models\NotificationDispatchLog;
use App\Services\Marketing\NotificationDispatchFailureResolver;
use App\Services\Marketing\NotificationDispatchLogData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PersistNotificationDispatchLogJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(NotificationDispatchFailureResolver $failureResolver): void
    {
        $data = NotificationDispatchLogData::fromArray($this->payload);
        $guidance = $failureResolver->resolve(
            status: $data->status,
            technicalMessage: $data->technicalMessage ?? $data->summary,
            channel: $data->channel,
        );

        NotificationDispatchLog::query()->create([
            'source' => $data->source->value,
            'status' => $data->status->value,
            'channel' => $data->channel?->value,
            'title' => $data->title,
            'summary' => mb_substr($data->summary, 0, 500),
            'failure_code' => $guidance['failure_code'] === 'none' ? null : $guidance['failure_code'],
            'analyst_message' => $guidance['analyst_message'],
            'resolution_steps' => $guidance['resolution_steps'],
            'sent_count' => $data->sentCount,
            'total_count' => $data->totalCount,
            'recipient' => $data->recipient,
            'batch_number' => $data->batchNumber,
            'total_batches' => $data->totalBatches,
            'birthday_notification_id' => $data->birthdayNotificationId,
            'mass_notification_id' => $data->massNotificationId,
            'corporate_event_id' => $data->corporateEventId,
            'sent_by_id' => $data->sentById,
            'technical_detail' => $data->technicalDetail,
            'logged_at' => now(),
        ]);
    }
}
