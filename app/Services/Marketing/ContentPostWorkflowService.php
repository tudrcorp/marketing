<?php

namespace App\Services\Marketing;

use App\Marketing\ContentPostStatus;
use App\Models\ContentPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mutaciones del tablero de contenido: arrastre entre columnas, reprogramación
 * desde el calendario y temporizador por pieza.
 */
class ContentPostWorkflowService
{
    /**
     * Mueve una pieza a otra columna del Kanban y persiste el orden resultante.
     *
     * @param  list<int|string>  $orderedIds  Identificadores de la columna destino, ya ordenados.
     */
    public function moveToStatus(ContentPost $post, ContentPostStatus $status, array $orderedIds = []): ContentPost
    {
        DB::transaction(function () use ($post, $status, $orderedIds): void {
            $post->status = $status;

            if ($status === ContentPostStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }

            if ($status !== ContentPostStatus::Published) {
                $post->published_at = null;
            }

            $post->save();

            $this->persistColumnOrder($status, $orderedIds, $post);
        });

        return $post->refresh();
    }

    /**
     * Reordena una columna sin cambiar de estado.
     *
     * @param  list<int|string>  $orderedIds
     */
    public function reorderColumn(ContentPostStatus $status, array $orderedIds): void
    {
        DB::transaction(fn () => $this->persistColumnOrder($status, $orderedIds));
    }

    /**
     * Reprograma una pieza conservando la hora original cuando existe.
     */
    public function reschedule(ContentPost $post, string $date): ContentPost
    {
        $target = Carbon::parse($date, config('app.timezone'));

        $post->scheduled_at = $post->scheduled_at !== null
            ? $target->copy()->setTimeFrom($post->scheduled_at->timezone(config('app.timezone')))
            : $target->copy()->setTime(9, 0);

        $post->save();

        return $post;
    }

    /**
     * Reordena la cuadrícula del perfil (simulador de feed).
     *
     * @param  list<int|string>  $orderedIds
     */
    public function reorderFeed(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                ContentPost::query()
                    ->whereKey($id)
                    ->update(['feed_position' => $position]);
            }
        });
    }

    /**
     * Arranca o detiene el temporizador, acumulando el tramo medido.
     */
    public function toggleTimer(ContentPost $post): ContentPost
    {
        if ($post->isTimerRunning()) {
            $post->time_spent_seconds = $post->elapsedSeconds();
            $post->timer_started_at = null;
        } else {
            $post->timer_started_at = now();
        }

        $post->save();

        return $post;
    }

    /**
     * @param  list<int|string>  $orderedIds
     */
    protected function persistColumnOrder(ContentPostStatus $status, array $orderedIds, ?ContentPost $movedPost = null): void
    {
        if ($orderedIds === []) {
            $movedPost?->forceFill([
                'board_position' => (int) ContentPost::query()
                    ->where('status', $status->value)
                    ->max('board_position') + 1,
            ])->save();

            return;
        }

        foreach (array_values($orderedIds) as $position => $id) {
            ContentPost::query()
                ->whereKey($id)
                ->update([
                    'status' => $status->value,
                    'board_position' => $position,
                ]);
        }
    }
}
