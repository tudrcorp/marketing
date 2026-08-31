<?php

namespace App\Livewire\CorporateEvents;

use App\Marketing\CorporateEventRegistrationGate;
use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventRegistrationService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.event-registration')]
#[Title('Inscripción al evento')]
class PublicRegistration extends Component
{
    public string $token;

    public string $full_name = '';

    public string $document_id = '';

    public string $phone = '';

    public string $email = '';

    public bool $submitted = false;

    public function mount(string $token): void
    {
        $this->token = $token;

        if ($this->resolveEvent() === null) {
            abort(404);
        }
    }

    public function getEventProperty(): ?CorporateEvent
    {
        return $this->resolveEvent();
    }

    public function getClosedReasonProperty(): ?string
    {
        $event = $this->event;

        if ($event === null) {
            return 'Evento no encontrado.';
        }

        return app(CorporateEventRegistrationGate::class)->closedReason($event);
    }

    public function getDeadlineLabelProperty(): ?string
    {
        $event = $this->event;

        if ($event?->registration_deadline === null) {
            return null;
        }

        return $event->registration_deadline
            ->timezone(config('app.timezone'))
            ->locale('es')
            ->isoFormat('dddd D [de] MMMM [de] YYYY [·] HH:mm');
    }

    public function submit(CorporateEventRegistrationService $registrationService): void
    {
        $event = $this->event;

        if ($event === null) {
            abort(404);
        }

        $validated = $this->validate([
            'full_name' => ['required', 'string', 'min:3', 'max:180'],
            'document_id' => ['required', 'string', 'min:6', 'max:20'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'email' => ['required', 'email', 'max:180'],
        ], [
            'full_name.required' => 'Indica tu nombre y apellido o razón social.',
            'document_id.required' => 'Indica tu cédula de identidad o RIF.',
            'phone.required' => 'Indica tu número de teléfono.',
            'email.required' => 'Indica tu correo electrónico.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        try {
            $registrationService->register($event, [
                ...$validated,
                'source' => 'public',
            ]);
        } catch (ValidationException $exception) {
            $this->resetErrorBag();

            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }

        $this->submitted = true;
        $this->reset(['full_name', 'document_id', 'phone', 'email']);
    }

    public function render()
    {
        return view('livewire.corporate-events.public-registration');
    }

    private function resolveEvent(): ?CorporateEvent
    {
        return CorporateEvent::query()
            ->where('registration_token', $this->token)
            ->first();
    }
}
