<?php

namespace App\Filament\Resources\TravelAgencies\Support;

use App\Support\DatePresentation;

class TravelAgencyPresentation
{
    public static function formatAge(mixed $age): ?string
    {
        if (! filled($age)) {
            return null;
        }

        return "{$age} años";
    }

    public static function formatDate(?string $date): ?string
    {
        return DatePresentation::format($date);
    }

    public static function identificationTypeLabel(?string $type): string
    {
        return match (strtoupper(trim($type ?? ''))) {
            'J' => 'Jurídica',
            'V' => 'Venezolano',
            'E' => 'Extranjero',
            'G' => 'Gubernamental',
            default => filled($type) ? strtoupper(trim($type)) : '—',
        };
    }

    public static function identificationTypeColor(?string $type): string
    {
        return match (strtoupper(trim($type ?? ''))) {
            'J' => 'primary',
            'V' => 'info',
            'E' => 'warning',
            'G' => 'gray',
            default => 'gray',
        };
    }

    public static function instagramUrl(?string $handle): ?string
    {
        if (! filled($handle)) {
            return null;
        }

        return 'https://instagram.com/'.ltrim($handle, '@');
    }

    public static function phoneUrl(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        return 'tel:'.preg_replace('/\s+/', '', $phone);
    }
}
