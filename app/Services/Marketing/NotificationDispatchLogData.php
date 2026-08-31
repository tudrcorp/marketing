<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;

class NotificationDispatchLogData
{
    /**
     * @param  array<string, mixed>|null  $technicalDetail
     */
    public function __construct(
        public NotificationDispatchSource $source,
        public NotificationDispatchStatus $status,
        public string $title,
        public string $summary,
        public ?BirthdayNotificationChannel $channel = null,
        public ?string $technicalMessage = null,
        public int $sentCount = 0,
        public int $totalCount = 0,
        public ?string $recipient = null,
        public ?int $birthdayNotificationId = null,
        public ?int $massNotificationId = null,
        public ?int $corporateEventId = null,
        public ?int $sentById = null,
        public ?int $batchNumber = null,
        public ?int $totalBatches = null,
        public ?array $technicalDetail = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'status' => $this->status->value,
            'channel' => $this->channel?->value,
            'title' => $this->title,
            'summary' => $this->summary,
            'technicalMessage' => $this->technicalMessage,
            'sentCount' => $this->sentCount,
            'totalCount' => $this->totalCount,
            'recipient' => $this->recipient,
            'birthdayNotificationId' => $this->birthdayNotificationId,
            'massNotificationId' => $this->massNotificationId,
            'corporateEventId' => $this->corporateEventId,
            'sentById' => $this->sentById,
            'batchNumber' => $this->batchNumber,
            'totalBatches' => $this->totalBatches,
            'technicalDetail' => $this->technicalDetail,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            source: NotificationDispatchSource::from((string) $payload['source']),
            status: NotificationDispatchStatus::from((string) $payload['status']),
            title: (string) $payload['title'],
            summary: (string) $payload['summary'],
            channel: filled($payload['channel'] ?? null)
                ? BirthdayNotificationChannel::tryFrom((string) $payload['channel'])
                : null,
            technicalMessage: filled($payload['technicalMessage'] ?? null)
                ? (string) $payload['technicalMessage']
                : null,
            sentCount: (int) ($payload['sentCount'] ?? 0),
            totalCount: (int) ($payload['totalCount'] ?? 0),
            recipient: filled($payload['recipient'] ?? null) ? (string) $payload['recipient'] : null,
            birthdayNotificationId: isset($payload['birthdayNotificationId']) ? (int) $payload['birthdayNotificationId'] : null,
            massNotificationId: isset($payload['massNotificationId']) ? (int) $payload['massNotificationId'] : null,
            corporateEventId: isset($payload['corporateEventId']) ? (int) $payload['corporateEventId'] : null,
            sentById: isset($payload['sentById']) ? (int) $payload['sentById'] : null,
            batchNumber: isset($payload['batchNumber']) ? (int) $payload['batchNumber'] : null,
            totalBatches: isset($payload['totalBatches']) ? (int) $payload['totalBatches'] : null,
            technicalDetail: is_array($payload['technicalDetail'] ?? null) ? $payload['technicalDetail'] : null,
        );
    }
}
