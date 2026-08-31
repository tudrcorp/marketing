<?php

namespace App\Services\Marketing;

use App\Marketing\CorporateEventStatus;
use App\Marketing\NotificationDispatchSource;
use App\Models\CorporateEvent;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CorporateEventPromotionService
{
    public function __construct(
        private CorporateEventCopyBuilder $copyBuilder,
        private MassNotificationDispatchService $dispatchService,
    ) {}

    /**
     * @param  list<string>  $channels
     */
    public function promote(CorporateEvent $event, array $channels, User $sentBy): MassNotificationDispatchResult
    {
        if (! $event->statusEnum()->isPromotable()) {
            throw ValidationException::withMessages([
                'status' => 'Solo eventos publicados o promocionados pueden difundirse.',
            ]);
        }

        if ($event->target_audiences === [] || $event->target_audiences === null) {
            throw ValidationException::withMessages([
                'target_audiences' => 'Define al menos un grupo destinatario antes de promocionar.',
            ]);
        }

        if ($channels === []) {
            throw ValidationException::withMessages([
                'channels' => 'Selecciona al menos un canal de envío.',
            ]);
        }

        $content = $this->copyBuilder->build($event);

        $result = $this->dispatchService->dispatchToAudiences(
            audiences: $event->target_audiences,
            data: [
                'title' => $content['title'],
                'copy' => $content['copy'],
                'channels' => $channels,
                'content_type' => null,
                'attachment' => $event->cover_image,
            ],
            sentBy: $sentBy,
            source: NotificationDispatchSource::CorporatePromotion,
            corporateEventId: $event->getKey(),
        );

        $event->update([
            'status' => CorporateEventStatus::Promoted->value,
            'promoted_channels' => $channels,
            'promoted_at' => now(),
            'mass_notification_id' => $result->notification->getKey(),
        ]);

        return $result;
    }

    public function publish(CorporateEvent $event): CorporateEvent
    {
        if ($event->statusEnum() !== CorporateEventStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Solo los borradores pueden publicarse.',
            ]);
        }

        $event->update([
            'status' => CorporateEventStatus::Published->value,
            'published_at' => now(),
        ]);

        return $event->refresh();
    }

    public function cancel(CorporateEvent $event): CorporateEvent
    {
        $event->update([
            'status' => CorporateEventStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        return $event->refresh();
    }

    public function complete(CorporateEvent $event): CorporateEvent
    {
        $event->update([
            'status' => CorporateEventStatus::Completed->value,
        ]);

        return $event->refresh();
    }

    public function markInProgress(CorporateEvent $event): CorporateEvent
    {
        $event->update([
            'status' => CorporateEventStatus::InProgress->value,
        ]);

        return $event->refresh();
    }
}
