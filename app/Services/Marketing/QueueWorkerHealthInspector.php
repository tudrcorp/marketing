<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Detecta si hay un worker consumiendo las colas antes de encolar trabajo pesado.
 *
 * Los lotes de correo y WhatsApp se procesan en segundo plano: si nadie consume la cola,
 * el envío queda encolado para siempre y el panel solo muestra "Encolando lote(s)…" sin
 * ningún error. Cada iteración del worker deja un latido en caché (ver `AppServiceProvider`)
 * y aquí se comprueba que ese latido siga fresco.
 */
class QueueWorkerHealthInspector
{
    private const HEARTBEAT_PREFIX = 'marketing:queue-worker-heartbeat:';

    /**
     * Margen de tolerancia del latido. El worker itera cada pocos segundos incluso sin
     * trabajo, pero puede quedarse ocupado en un lote largo, así que se concede holgura.
     */
    private const HEARTBEAT_TTL_SECONDS = 180;

    public function recordHeartbeat(string $queue): void
    {
        Cache::put(
            self::HEARTBEAT_PREFIX.$queue,
            now()->timestamp,
            self::HEARTBEAT_TTL_SECONDS,
        );
    }

    public function isWorkerRunning(string $queue): bool
    {
        $lastBeat = Cache::get(self::HEARTBEAT_PREFIX.$queue);

        if (! is_numeric($lastBeat)) {
            return false;
        }

        return (now()->timestamp - (int) $lastBeat) <= self::HEARTBEAT_TTL_SECONDS;
    }

    public function pendingJobs(string $queue): int
    {
        try {
            return Queue::size($queue);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Mensaje para el analista cuando alguna cola no tiene worker, o `null` si están sanas.
     *
     * @param  list<string>  $queues  Colas que el envío necesita para completarse.
     */
    public function unavailableMessage(array $queues = ['default', 'email']): ?string
    {
        // Con la conexión `sync` no existe cola: cada job corre en el acto dentro de la
        // misma petición, así que no hace falta ningún worker.
        if (config('queue.default') === 'sync') {
            return null;
        }

        $stalled = array_values(array_filter(
            $queues,
            fn (string $queue): bool => ! $this->isWorkerRunning($queue),
        ));

        if ($stalled === []) {
            return null;
        }

        $pending = array_sum(array_map(fn (string $queue): int => $this->pendingJobs($queue), $stalled));

        $message = 'No hay ningún worker procesando la(s) cola(s) «'.implode('», «', $stalled).'», '
            .'así que el envío quedaría encolado sin salir. Levanta el worker con `composer run dev` '
            .'(o `php artisan queue:work --queue=default,email`) y vuelve a intentarlo.';

        if ($pending > 0) {
            $message .= ' Ya hay '.$pending.' lote(s) en espera: al levantar el worker se procesarán '
                .'solos, sin necesidad de relanzar la campaña.';
        }

        return $message;
    }
}
