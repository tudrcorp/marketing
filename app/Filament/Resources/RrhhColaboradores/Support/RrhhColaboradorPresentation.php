<?php

namespace App\Filament\Resources\RrhhColaboradores\Support;

use App\Support\DatePresentation;

class RrhhColaboradorPresentation
{
    public static function formatAge(mixed $age): ?string
    {
        if (! filled($age) || ! is_numeric($age) || (int) $age > 120) {
            return null;
        }

        return "{$age} años";
    }

    public static function formatDate(?string $date): ?string
    {
        return DatePresentation::format($date);
    }

    public static function sexLabel(?string $sex): string
    {
        return match (strtoupper(trim($sex ?? ''))) {
            'MASCULINO' => 'Masculino',
            'FEMENINO' => 'Femenino',
            default => filled($sex) ? trim($sex) : '—',
        };
    }

    public static function sexColor(?string $sex): string
    {
        return match (strtoupper(trim($sex ?? ''))) {
            'MASCULINO' => 'info',
            'FEMENINO' => 'primary',
            default => 'gray',
        };
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
