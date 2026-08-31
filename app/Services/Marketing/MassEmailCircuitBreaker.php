<?php

namespace App\Services\Marketing;

use App\Marketing\EmailDispatchFailureKind;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Freno de emergencia del canal de correo.
 *
 * Cuando el proveedor responde «cuota agotada» o bloquea la autenticación, el problema
 * es del remitente y no de un lote concreto: cualquier otro lote chocaría igual. Este
 * freno se abre al primer fallo de ese tipo y hace que los lotes pendientes se
 * reprogramen sin llegar a llamar al API, en vez de quemarse todos en cadena.
 *
 * Vive en caché (como el latido de `QueueWorkerHealthInspector`) para que lo compartan
 * todos los workers sin depender de Events/Listeners, que este proyecto no usa.
 */
class MassEmailCircuitBreaker
{
    private const string CacheKey = 'marketing:mass-email-circuit';

    public function trip(EmailDispatchFailureKind $kind, string $reason, int $cooldownSeconds): void
    {
        $cooldownSeconds = max(60, $cooldownSeconds);
        $until = now()->addSeconds($cooldownSeconds);
        $current = $this->state();

        // Si ya hay un freno con una espera más larga, respetarla: bajarla solo
        // adelantaría el próximo choque contra el mismo límite.
        if ($current !== null && $current['until'] >= $until->timestamp) {
            return;
        }

        Cache::put(self::CacheKey, [
            'kind' => $kind->value,
            'reason' => $reason,
            'until' => $until->timestamp,
            'opened_at' => now()->timestamp,
        ], $until->addMinute());
    }

    public function isOpen(): bool
    {
        return $this->state() !== null;
    }

    /**
     * @return array{kind: string, reason: string, until: int, opened_at: int}|null
     */
    public function state(): ?array
    {
        $state = Cache::get(self::CacheKey);

        if (! is_array($state) || ! isset($state['until'])) {
            return null;
        }

        if ((int) $state['until'] <= now()->timestamp) {
            $this->clear();

            return null;
        }

        /** @var array{kind: string, reason: string, until: int, opened_at: int} $state */
        return $state;
    }

    public function kind(): ?EmailDispatchFailureKind
    {
        $state = $this->state();

        return $state === null
            ? null
            : EmailDispatchFailureKind::tryFrom($state['kind']);
    }

    public function reason(): ?string
    {
        return $this->state()['reason'] ?? null;
    }

    public function retryAt(): ?CarbonInterface
    {
        $state = $this->state();

        return $state === null ? null : now()->setTimestamp($state['until']);
    }

    /**
     * Segundos que faltan para reanudar. Nunca menos de 60 para no reencolar en bucle.
     */
    public function secondsUntilRetry(): int
    {
        $state = $this->state();

        if ($state === null) {
            return 0;
        }

        return max(60, $state['until'] - now()->timestamp);
    }

    /**
     * Mensaje listo para el analista, con la hora de reanudación.
     */
    public function pauseMessage(): ?string
    {
        $state = $this->state();

        if ($state === null) {
            return null;
        }

        $kind = EmailDispatchFailureKind::tryFrom($state['kind']) ?? EmailDispatchFailureKind::Unknown;

        return $kind->label().'. Los lotes pendientes se reanudan solos a las '
            .$this->retryAt()?->format('H:i').'; no relances la campaña o duplicarás los correos ya enviados.';
    }

    public function clear(): void
    {
        Cache::forget(self::CacheKey);
    }
}
