<?php

namespace App\Filament\Resources\CorporateEvents\Support;

use App\Marketing\CorporateEventRegistrationGate;
use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventCopyBuilder;

class CorporateEventSharePresentation
{
    public function __construct(
        private CorporateEventRegistrationGate $registrationGate,
        private CorporateEventCopyBuilder $copyBuilder,
    ) {}

    public function shareMessage(CorporateEvent $event): string
    {
        $content = $this->copyBuilder->build($event);

        return $content['copy'];
    }

    public function mailtoUrl(CorporateEvent $event): string
    {
        $subject = rawurlencode('Invitación: '.$event->title);
        $body = rawurlencode($this->shareMessage($event));

        return "mailto:?subject={$subject}&body={$body}";
    }

    public function whatsappUrl(CorporateEvent $event): string
    {
        return 'https://wa.me/?text='.rawurlencode($this->shareMessage($event));
    }

    public function smsUrl(CorporateEvent $event): string
    {
        return 'sms:?&body='.rawurlencode($this->shareMessage($event));
    }

    public function registrationStatusLabel(CorporateEvent $event): string
    {
        $closedReason = $this->registrationGate->closedReason($event);

        if ($closedReason !== null) {
            return 'Inscripciones cerradas';
        }

        if ($event->registration_deadline !== null) {
            return 'Abierto hasta '.$event->registration_deadline
                ->timezone(config('app.timezone'))
                ->locale('es')
                ->isoFormat('D MMM · HH:mm');
        }

        return 'Inscripciones abiertas';
    }

    public function registrationStatusColor(CorporateEvent $event): string
    {
        return $this->registrationGate->isOpen($event) ? 'success' : 'danger';
    }
}
