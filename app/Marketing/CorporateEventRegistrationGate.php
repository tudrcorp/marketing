<?php

namespace App\Marketing;

use App\Models\CorporateEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CorporateEventRegistrationGate
{
    public function isOpen(CorporateEvent $event): bool
    {
        return $this->closedReason($event) === null;
    }

    public function closedReason(CorporateEvent $event): ?string
    {
        if ($event->statusEnum() === CorporateEventStatus::Draft) {
            return 'Este evento aún no está publicado. Publícalo desde el panel de marketing para abrir las inscripciones.';
        }

        if (in_array($event->statusEnum(), [
            CorporateEventStatus::Cancelled,
            CorporateEventStatus::Completed,
        ], true)) {
            return 'Este evento no está disponible para inscripciones.';
        }

        if ($event->registration_deadline !== null && $this->registrationDeadlineHasPassed($event->registration_deadline)) {
            return 'El periodo de inscripciones finalizó el '.$this->formatDeadline($event->registration_deadline);
        }

        if ($event->isFull()) {
            return 'El evento alcanzó su cupo máximo de inscripciones.';
        }

        return null;
    }

    private function registrationDeadlineHasPassed(CarbonInterface $deadline): bool
    {
        return $this->nowInAppTimezone()->greaterThan($this->deadlineInAppTimezone($deadline));
    }

    private function formatDeadline(CarbonInterface $deadline): string
    {
        return $this->deadlineInAppTimezone($deadline)
            ->locale('es')
            ->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm');
    }

    private function nowInAppTimezone(): CarbonInterface
    {
        return now()->timezone(config('app.timezone'));
    }

    private function deadlineInAppTimezone(CarbonInterface $deadline): CarbonInterface
    {
        return Carbon::instance($deadline)->timezone(config('app.timezone'));
    }
}
