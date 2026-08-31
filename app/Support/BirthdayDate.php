<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

class BirthdayDate
{
    public static function isBirthdayToday(?string $date, ?Carbon $reference = null): bool
    {
        if (! filled($date)) {
            return false;
        }

        $parsed = self::parse($date);

        if ($parsed === null) {
            return false;
        }

        $reference ??= Carbon::today();

        return $parsed->month === $reference->month
            && $parsed->day === $reference->day;
    }

    public static function parse(?string $date): ?Carbon
    {
        if (! filled($date)) {
            return null;
        }

        $date = trim($date);

        try {
            $parsed = preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)
                ? Carbon::createFromFormat('d/m/Y', $date)
                : Carbon::parse($date);

            if ($parsed->year < 1900) {
                return null;
            }

            return $parsed;
        } catch (Throwable) {
            return null;
        }
    }
}
