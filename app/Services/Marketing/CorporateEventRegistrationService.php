<?php

namespace App\Services\Marketing;

use App\Marketing\CorporateEventRegistrationGate;
use App\Marketing\CorporateEventRegistrationStatus;
use App\Models\CorporateEvent;
use App\Models\CorporateEventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CorporateEventRegistrationService
{
    public function __construct(
        private CorporateEventRegistrationGate $registrationGate,
    ) {}

    /**
     * @param  array{
     *     full_name: string,
     *     document_id?: string|null,
     *     email: string,
     *     phone?: string|null,
     *     company?: string|null,
     *     audience_source?: string|null,
     *     status?: string,
     *     source?: string,
     * }  $data
     */
    public function register(CorporateEvent $event, array $data, ?User $registeredBy = null): CorporateEventRegistration
    {
        return DB::transaction(function () use ($event, $data, $registeredBy): CorporateEventRegistration {
            $lockedEvent = CorporateEvent::query()
                ->whereKey($event->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $closedReason = $this->registrationGate->closedReason($lockedEvent);

            if ($closedReason !== null) {
                throw ValidationException::withMessages([
                    'registration' => $closedReason,
                ]);
            }

            $email = mb_strtolower(trim($data['email']));
            $documentId = $this->normalizeDocumentId($data['document_id'] ?? null);

            if ($lockedEvent->registrations()->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Este correo ya está inscrito en el evento.',
                ]);
            }

            if ($documentId !== null && $lockedEvent->registrations()->where('document_id', $documentId)->exists()) {
                throw ValidationException::withMessages([
                    'document_id' => 'Esta cédula o RIF ya está inscrita en el evento.',
                ]);
            }

            $registration = $lockedEvent->registrations()->create([
                'full_name' => trim($data['full_name']),
                'document_id' => $documentId,
                'email' => $email,
                'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
                'company' => filled($data['company'] ?? null) ? trim((string) $data['company']) : null,
                'audience_source' => $data['audience_source'] ?? null,
                'status' => $data['status'] ?? CorporateEventRegistrationStatus::Registered->value,
                'source' => $data['source'] ?? 'manual',
                'registered_by_id' => $registeredBy?->getKey(),
                'registered_at' => now(),
            ]);

            $lockedEvent->increment('registrations_count');

            return $registration;
        });
    }

    public function markAttended(CorporateEventRegistration $registration): CorporateEventRegistration
    {
        $registration->update([
            'status' => CorporateEventRegistrationStatus::Attended->value,
        ]);

        return $registration->refresh();
    }

    public function cancel(CorporateEventRegistration $registration): CorporateEventRegistration
    {
        if ($registration->statusEnum() === CorporateEventRegistrationStatus::Cancelled) {
            return $registration;
        }

        return DB::transaction(function () use ($registration): CorporateEventRegistration {
            $registration->update([
                'status' => CorporateEventRegistrationStatus::Cancelled->value,
            ]);

            CorporateEvent::query()
                ->whereKey($registration->corporate_event_id)
                ->where('registrations_count', '>', 0)
                ->decrement('registrations_count');

            return $registration->refresh();
        });
    }

    private function normalizeDocumentId(?string $documentId): ?string
    {
        if (blank($documentId)) {
            return null;
        }

        return Str::upper(preg_replace('/\s+/', '', trim($documentId)));
    }
}
