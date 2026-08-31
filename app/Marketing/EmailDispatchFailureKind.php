<?php

namespace App\Marketing;

/**
 * Naturaleza de un fallo de envío de correo, para decidir si conviene reintentar,
 * cuánto esperar y si hay que frenar toda la campaña.
 *
 * El caso que motivó esto: Gmail respondió `550 5.4.5 Daily user sending limit exceeded`
 * a mitad de una campaña y los lotes restantes se dispararon igual, quemándose en dos
 * segundos contra el mismo muro y perdiendo 359 destinatarios sin reintento.
 */
enum EmailDispatchFailureKind: string
{
    /** La cuota diaria del proveedor se agotó: no sirve reintentar pronto ni seguir con los demás lotes. */
    case QuotaExceeded = 'quota_exceeded';

    /** El proveedor bloqueó la autenticación por exceso de intentos de login (suele ser secuela de la cuota). */
    case AuthThrottled = 'auth_throttled';

    /** Corte de red, timeout o error temporal del proveedor: reintentar suele bastar. */
    case Transient = 'transient';

    /** Buzón inexistente o rechazo definitivo: reintentar solo repite el mismo error. */
    case Permanent = 'permanent';

    /** No se detectó un patrón conocido. */
    case Unknown = 'unknown';

    /**
     * Un fallo de cuota o de autenticación afecta a todo el remitente, no solo a este lote:
     * mientras dure, cualquier otro lote chocaría igual.
     */
    public function shouldTripCircuit(): bool
    {
        return in_array($this, [self::QuotaExceeded, self::AuthThrottled], true);
    }

    public function isRetryable(): bool
    {
        return in_array($this, [self::QuotaExceeded, self::AuthThrottled, self::Transient], true);
    }

    /**
     * Segundos de espera antes del siguiente intento.
     *
     * @param  int  $attempt  Número del intento que acaba de fallar (1 = primero).
     */
    public function cooldownSeconds(int $attempt = 1): int
    {
        $minutes = match ($this) {
            // Gmail/Workspace aplican una ventana móvil de 24 h: esperar minutos no la libera,
            // pero reintentar cada hora recupera la campaña sola en cuanto entra cuota nueva.
            self::QuotaExceeded => (int) config('services.marketing_api.mass_email_quota_cooldown_minutes', 60),
            self::AuthThrottled => (int) config('services.marketing_api.mass_email_auth_cooldown_minutes', 15),
            default => 0,
        };

        if ($minutes > 0) {
            return $minutes * 60;
        }

        // Backoff progresivo para cortes temporales: 1, 2, 4, 8… minutos, con techo de 15.
        return min(900, 60 * (2 ** max(0, $attempt - 1)));
    }

    public function label(): string
    {
        return match ($this) {
            self::QuotaExceeded => 'Cuota diaria de correo agotada',
            self::AuthThrottled => 'Autenticación de correo bloqueada temporalmente',
            self::Transient => 'Fallo temporal del proveedor de correo',
            self::Permanent => 'Rechazo definitivo del proveedor de correo',
            self::Unknown => 'Fallo de correo no clasificado',
        };
    }
}
