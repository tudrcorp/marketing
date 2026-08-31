<?php

namespace App\Support;

class AffiliatePresentation
{
    public static function formatAge(mixed $age): ?string
    {
        if (! filled($age)) {
            return null;
        }

        return "{$age} años";
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

    public static function phoneUrl(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        return 'tel:'.preg_replace('/\s+/', '', $phone);
    }

    public static function displayEmail(?string $email): ?string
    {
        return filled(trim($email ?? '')) ? trim($email) : null;
    }
}
