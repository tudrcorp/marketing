<?php

namespace App\Services\Marketing;

use InvalidArgumentException;

class MarketingPhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        $normalized = preg_replace('/\s+/', '', trim($phone)) ?? trim($phone);

        if (str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        }

        if (str_starts_with($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }

        if (str_starts_with($normalized, '0') && strlen($normalized) === 11) {
            $normalized = '58'.substr($normalized, 1);
        }

        if (! preg_match('/^[0-9]{8,15}$/', $normalized)) {
            throw new InvalidArgumentException("Formato de teléfono inválido: {$phone}");
        }

        return $normalized;
    }

    public static function tryNormalize(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        try {
            return self::normalize($phone);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
