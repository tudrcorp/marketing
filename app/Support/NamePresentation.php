<?php

namespace App\Support;

class NamePresentation
{
    public static function uppercase(?string $name): ?string
    {
        if (! filled($name)) {
            return null;
        }

        return mb_strtoupper(trim($name));
    }
}
