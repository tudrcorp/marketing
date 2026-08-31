<?php

namespace App\Services\Marketing;

use App\Marketing\CorporateEventRegistrationGate;
use App\Models\CorporateEvent;

class CorporateEventCopyBuilder
{
    /**
     * @return array{title: string, copy: string}
     */
    public function build(CorporateEvent $event): array
    {
        $startsAt = $event->starts_at->timezone(config('app.timezone'))->locale('es');
        $dateLabel = $startsAt->isoFormat('dddd D [de] MMMM [de] YYYY · HH:mm');
        $modality = $event->modalityEnum()?->getLabel() ?? 'Por confirmar';
        $type = $event->typeEnum()?->getLabel() ?? 'Evento corporativo';

        $location = match (true) {
            filled($event->venue_name) && filled($event->venue_address) => "{$event->venue_name} — {$event->venue_address}",
            filled($event->venue_name) => $event->venue_name,
            filled($event->virtual_url) => 'Enlace virtual: '.$event->virtual_url,
            default => 'Ubicación por confirmar',
        };

        $lines = [
            "Te invitamos a {$event->title}.",
            "Tipo: {$type}",
            "Modalidad: {$modality}",
            "Fecha: {$dateLabel}",
            "Lugar: {$location}",
        ];

        if (filled($event->summary)) {
            $lines[] = $event->summary;
        }

        if (filled($event->registration_url)) {
            $lines[] = 'Inscríbete aquí: '.$event->registration_url;
        } elseif ($event->hasCapacity()) {
            $remaining = $event->remainingCapacity();

            if ($remaining !== null) {
                $lines[] = "Cupos disponibles: {$remaining}.";
            }
        }

        $lines[] = 'Equipo TDG Marketing';

        return [
            'title' => 'Invitación: '.$event->title,
            'copy' => implode("\n\n", $lines),
        ];
    }

    public function formatScheduleLabel(CorporateEvent $event): string
    {
        return $event->starts_at
            ->timezone(config('app.timezone'))
            ->locale('es')
            ->isoFormat('dddd D [de] MMMM · HH:mm');
    }

    public function isRegistrationOpen(CorporateEvent $event): bool
    {
        return app(CorporateEventRegistrationGate::class)->isOpen($event);
    }
}
