<?php

namespace App\Services\Marketing;

/**
 * Ritmo de los lotes de correo masivo, para no saturar Gmail/Workspace.
 */
class MassEmailDispatchPace
{
    public static function batchSize(): int
    {
        return max(1, (int) config('services.marketing_api.mass_email_batch_size', 15));
    }

    public static function pauseSeconds(): int
    {
        return max(0, (int) config('services.marketing_api.mass_email_batch_pause_seconds', 30));
    }

    /**
     * Aviso para el analista en el modal de envío: duración aproximada y no relanzar.
     */
    public static function analystWarning(?int $recipientCount = null): string
    {
        $batchSize = self::batchSize();
        $pause = self::pauseSeconds();
        $warning = "Los correos salen de a {$batchSize}, con {$pause} s entre lotes, para no saturar Gmail.";

        if (is_int($recipientCount) && $recipientCount > 0) {
            $batches = (int) ceil($recipientCount / $batchSize);
            // Pausa entre lotes de Laravel + ~2 s por correo (SMTP de a uno en el API).
            $seconds = max(0, ($batches - 1) * $pause) + ($recipientCount * 2);
            $minutes = max(1, (int) ceil($seconds / 60));
            $warning .= " Este envío (~{$recipientCount} destinatarios) puede tardar unos {$minutes} min.";
        }

        return $warning.' Si Google pausa la cuenta, no relances: los pendientes se reanudan solos.';
    }
}
