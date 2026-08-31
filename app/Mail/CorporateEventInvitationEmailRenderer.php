<?php

namespace App\Mail;

use App\Filament\Resources\CorporateEvents\Support\CorporateEventPresentation;
use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventCopyBuilder;
use Illuminate\Support\Facades\View;

class CorporateEventInvitationEmailRenderer
{
    public function __construct(
        private CorporateEventCopyBuilder $copyBuilder,
    ) {}

    public function render(
        CorporateEvent $event,
        string $message,
        ?string $sentByName = null,
    ): string {
        return View::make('mail.corporate-event-invitation', [
            'event' => $event,
            'message' => $message,
            'sentByName' => $sentByName,
            'dateLabel' => $this->copyBuilder->formatScheduleLabel($event),
            'typeLabel' => $event->typeEnum()?->getLabel() ?? 'Evento corporativo',
            'modalityLabel' => $event->modalityEnum()?->getLabel() ?? 'Por confirmar',
            'locationLabel' => $this->resolveLocationLabel($event),
            'registrationUrl' => $event->registration_url,
            'useEmbeddedCoverImage' => CorporateEventPresentation::hasCoverImage($event),
        ])->render();
    }

    private function resolveLocationLabel(CorporateEvent $event): string
    {
        return match (true) {
            filled($event->venue_name) && filled($event->venue_address) => "{$event->venue_name} — {$event->venue_address}",
            filled($event->venue_name) => $event->venue_name,
            filled($event->virtual_url) => 'Evento virtual',
            default => 'Ubicación por confirmar',
        };
    }
}
