<?php

namespace App\Filament\Resources\BrokerAgencies\Support;

class BrokerAgencyPresentation
{
    public static function agencyTypeLabel(?string $typeId): string
    {
        return match ($typeId) {
            '1' => 'MASTER',
            '2' => 'Corredor',
            '3' => 'GENERAL',
            default => filled($typeId) ? "Tipo {$typeId}" : 'Sin tipo',
        };
    }

    public static function agencyTypeColor(?string $typeId): string
    {
        return match ($typeId) {
            '1' => 'info',
            '2' => 'primary',
            '3' => 'warning',
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

    public static function hasSecondaryContact(array $record): bool
    {
        return filled($record['name_contact_2'] ?? null)
            || filled($record['email_contact_2'] ?? null)
            || filled($record['phone_contact_2'] ?? null);
    }
}
