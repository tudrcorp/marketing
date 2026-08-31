<?php

namespace App\Filament\Resources\TravelAgents\Support;

use App\Support\DatePresentation;

class TravelAgentPresentation
{
    public static function formatAge(mixed $age): ?string
    {
        if (! filled($age) || ! is_numeric($age) || (int) $age > 120) {
            return null;
        }

        return "{$age} años";
    }

    public static function formatBirthDate(?string $birthDate): ?string
    {
        return DatePresentation::format($birthDate);
    }

    public static function displayEmail(?string $email): ?string
    {
        if (! filled($email)) {
            return null;
        }

        return trim($email);
    }

    public static function phoneUrl(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        return 'tel:'.preg_replace('/\s+/', '', $phone);
    }
}
