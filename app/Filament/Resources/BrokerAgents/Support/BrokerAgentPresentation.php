<?php

namespace App\Filament\Resources\BrokerAgents\Support;

use Carbon\Carbon;
use Throwable;

class BrokerAgentPresentation
{
    public static function agencyTypeLabel(?string $typeId): string
    {
        return match ($typeId) {
            '1' => 'Agencia',
            '2' => 'Corredor',
            default => filled($typeId) ? "Tipo {$typeId}" : 'Sin tipo',
        };
    }

    public static function agencyTypeColor(?string $typeId): string
    {
        return match ($typeId) {
            '1' => 'info',
            '2' => 'primary',
            default => 'gray',
        };
    }

    public static function formatAge(mixed $age): ?string
    {
        if (! filled($age)) {
            return null;
        }

        return "{$age} años";
    }

    public static function formatBirthDate(?string $birthDate): ?string
    {
        if (! filled($birthDate)) {
            return null;
        }

        try {
            return Carbon::parse($birthDate)->format('d/m/Y');
        } catch (Throwable) {
            return trim($birthDate);
        }
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
