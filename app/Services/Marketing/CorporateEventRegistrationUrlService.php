<?php

namespace App\Services\Marketing;

use App\Models\CorporateEvent;
use Illuminate\Support\Str;

class CorporateEventRegistrationUrlService
{
    public function generateToken(): string
    {
        return Str::lower(Str::random(48));
    }

    public function buildUrl(string $token): string
    {
        return route('corporate-events.register', ['token' => $token]);
    }

    public function ensureForEvent(CorporateEvent $event): CorporateEvent
    {
        if (filled($event->registration_token) && filled($event->registration_url)) {
            return $event;
        }

        $token = $this->generateToken();

        $event->update([
            'registration_token' => $token,
            'registration_url' => $this->buildUrl($token),
        ]);

        return $event->refresh();
    }
}
