<?php

namespace App\Services\Marketing;

use App\Jobs\PersistNotificationDispatchLogJob;
use App\Marketing\NotificationDispatchStatus;

class NotificationDispatchLogger
{
    public function __construct(
        private NotificationDispatchFailureResolver $failureResolver,
    ) {}

    public function log(NotificationDispatchLogData $data): void
    {
        // Persistir en el momento para que floater/view/historial reflejen el resultado
        // sin depender de un worker de cola.
        $this->persist($data);
    }

    public function logSync(NotificationDispatchLogData $data): void
    {
        $this->persist($data);
    }

    private function persist(NotificationDispatchLogData $data): void
    {
        (new PersistNotificationDispatchLogJob($data->toArray()))
            ->handle($this->failureResolver);
    }

    public function logChannelResult(
        NotificationDispatchLogData $base,
        bool $successful,
        string $message,
        int $sent = 0,
        int $total = 0,
    ): void {
        $status = match (true) {
            str_contains(mb_strtolower($message), 'encol') => NotificationDispatchStatus::Queued,
            $successful && $sent > 0 && $sent < $total => NotificationDispatchStatus::Partial,
            $successful => NotificationDispatchStatus::Sent,
            str_contains(mb_strtolower($message), 'aún no está configurado') => NotificationDispatchStatus::Skipped,
            default => NotificationDispatchStatus::Failed,
        };

        $this->log(new NotificationDispatchLogData(
            source: $base->source,
            status: $status,
            title: $base->title,
            summary: $message,
            channel: $base->channel,
            technicalMessage: $message,
            sentCount: $sent,
            totalCount: $total,
            recipient: $base->recipient,
            birthdayNotificationId: $base->birthdayNotificationId,
            massNotificationId: $base->massNotificationId,
            corporateEventId: $base->corporateEventId,
            sentById: $base->sentById,
            batchNumber: $base->batchNumber,
            totalBatches: $base->totalBatches,
            technicalDetail: $base->technicalDetail,
        ));
    }
}
