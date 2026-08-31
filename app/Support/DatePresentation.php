<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

class DatePresentation
{
    public static function format(?string $date): ?string
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

            return $parsed->format('d/m/Y');
        } catch (Throwable) {
            return $date;
        }
    }
}
