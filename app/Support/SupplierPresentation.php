<?php

namespace App\Support;

class SupplierPresentation
{
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

    public static function convenioColor(?string $status): string
    {
        return match (strtoupper(trim($status ?? ''))) {
            'MASTER' => 'warning',
            'GENERAL' => 'primary',
            default => 'gray',
        };
    }

    public static function sistemaColor(?string $status): string
    {
        return match (strtoupper(trim($status ?? ''))) {
            'AFILIADO' => 'success',
            'EN PROCESO' => 'warning',
            'SIN RESPUESTA DE AFILIACION' => 'danger',
            default => 'gray',
        };
    }
}
