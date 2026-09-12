<?php

namespace App\Services\Marketing;

use App\Marketing\ContentPillar;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostStatus;
use App\Models\ContentPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lecturas y agregados del panel máster de contenido multimarca:
 * tablero Kanban, calendario mensual/semanal, semáforo de carga y balance de pilares.
 *
 * @phpstan-type BoardFilters array{
 *     brand_id?: int|null,
 *     priority?: string|null,
 *     format?: string|null,
 *     pillar?: string|null,
 *     blocker?: string|null,
 *     batching?: string|null,
 *     search?: string|null,
 *     only_blocked?: bool,
 * }
 */
class ContentBoardService
{
    /**
     * Entregas por día a partir de las cuales el calendario enciende cada nivel del semáforo.
     */
    public const WorkloadModerateThreshold = 3;

    public const WorkloadHighThreshold = 5;

    public const WorkloadSaturatedThreshold = 7;

    /**
     * @param  BoardFilters  $filters
     * @return Builder<ContentPost>
     */
    public function query(array $filters): Builder
    {
        return ContentPost::query()
            ->with(['brand:id,name,color_hex'])
            ->forBrand($filters['brand_id'] ?? null)
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority): Builder => $query->where('priority', $priority))
            ->when($filters['format'] ?? null, fn (Builder $query, string $format): Builder => $query->where('format', $format))
            ->when($filters['pillar'] ?? null, fn (Builder $query, string $pillar): Builder => $query->where('pillar', $pillar))
            ->when($filters['blocker'] ?? null, fn (Builder $query, string $blocker): Builder => $query->where('blocker', $blocker))
            ->when($filters['batching'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['only_blocked'] ?? false, fn (Builder $query): Builder => $query->blocked())
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(
                fn (Builder $builder): Builder => $builder
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('copy', 'like', '%'.$search.'%')
            ));
    }

    /**
     * Columnas del Kanban en el orden del flujo de producción.
     *
     * @param  BoardFilters  $filters
     * @return list<array{status: ContentPostStatus, label: string, color: string, count: int, posts: Collection<int, ContentPost>}>
     */
    public function kanbanColumns(array $filters): array
    {
        $grouped = $this->query($filters)
            ->orderBy('board_position')
            ->orderByUrgency()
            ->get()
            ->groupBy(fn (ContentPost $post): string => $post->status->value);

        return collect(ContentPostStatus::orderedCases())
            ->map(function (ContentPostStatus $status) use ($grouped): array {
                /** @var Collection<int, ContentPost> $posts */
                $posts = $grouped->get($status->value, collect());

                return [
                    'status' => $status,
                    'label' => $status->getLabel(),
                    'color' => $status->getHexColor(),
                    'count' => $posts->count(),
                    'posts' => $posts->values(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Piezas ordenadas por urgencia global — alimenta el Modo Máster.
     *
     * @param  BoardFilters  $filters
     * @return Collection<int, ContentPost>
     */
    public function urgentQueue(array $filters, int $limit = 25): Collection
    {
        return $this->query($filters)
            ->where('status', '!=', ContentPostStatus::Published->value)
            ->orderByUrgency()
            ->limit($limit)
            ->get();
    }

    /**
     * Cuadrícula del mes con semáforo de carga laboral por día.
     *
     * @param  BoardFilters  $filters
     * @return list<list<array{date: string, day: int, isCurrentMonth: bool, isToday: bool, isSelected: bool, count: int, workload: string, posts: list<array<string, mixed>>}>>
     */
    public function monthWeeks(array $filters, string $month, string $selectedDate): array
    {
        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $summaries = $this->daySummaries($filters, $gridStart, $gridEnd);

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $week[] = $this->dayCell($cursor, $summaries, $selectedDate, $cursor->month === $monthStart->month);
                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * Días de la semana ancla, con el mismo semáforo que la vista mensual.
     *
     * @param  BoardFilters  $filters
     * @return list<array{date: string, day: int, isCurrentMonth: bool, isToday: bool, isSelected: bool, count: int, workload: string, posts: list<array<string, mixed>>}>
     */
    public function weekDays(array $filters, string $anchorDate, string $selectedDate): array
    {
        $weekStart = Carbon::parse($anchorDate)->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $summaries = $this->daySummaries($filters, $weekStart, $weekEnd);

        $days = [];
        $cursor = $weekStart->copy();

        while ($cursor->lte($weekEnd)) {
            $days[] = $this->dayCell($cursor, $summaries, $selectedDate, true);
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * Nivel del semáforo de carga laboral para un número de entregas en un día.
     */
    public static function workloadLevel(int $count): string
    {
        return match (true) {
            $count === 0 => 'empty',
            $count < self::WorkloadModerateThreshold => 'light',
            $count < self::WorkloadHighThreshold => 'moderate',
            $count < self::WorkloadSaturatedThreshold => 'high',
            default => 'saturated',
        };
    }

    /**
     * @return list<array{level: string, label: string, description: string}>
     */
    public static function workloadLegend(): array
    {
        return [
            ['level' => 'light', 'label' => 'Carga baja', 'description' => 'Hasta '.(self::WorkloadModerateThreshold - 1).' entregas'],
            ['level' => 'moderate', 'label' => 'Carga media', 'description' => self::WorkloadModerateThreshold.' a '.(self::WorkloadHighThreshold - 1).' entregas'],
            ['level' => 'high', 'label' => 'Carga alta', 'description' => self::WorkloadHighThreshold.' a '.(self::WorkloadSaturatedThreshold - 1).' entregas'],
            ['level' => 'saturated', 'label' => 'Día saturado', 'description' => self::WorkloadSaturatedThreshold.' o más entregas'],
        ];
    }

    /**
     * Cuellos de botella activos, agrupados por tipo de bloqueo.
     *
     * @param  BoardFilters  $filters
     * @return list<array{blocker: ContentPostBlocker, count: int, posts: Collection<int, ContentPost>}>
     */
    public function blockerGroups(array $filters): array
    {
        $grouped = $this->query($filters)
            ->blocked()
            ->orderByUrgency()
            ->get()
            ->groupBy(fn (ContentPost $post): string => $post->blocker->value);

        return collect(ContentPostBlocker::blockingCases())
            ->map(function (ContentPostBlocker $blocker) use ($grouped): array {
                /** @var Collection<int, ContentPost> $posts */
                $posts = $grouped->get($blocker->value, collect());

                return [
                    'blocker' => $blocker,
                    'count' => $posts->count(),
                    'posts' => $posts->values(),
                ];
            })
            ->filter(fn (array $group): bool => $group['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * Matriz de balance de pilares de la selección activa.
     *
     * @param  BoardFilters  $filters
     * @return list<array{pillar: ContentPillar, label: string, color: string, count: int, percentage: float}>
     */
    public function pillarBalance(array $filters): array
    {
        $counts = $this->query($filters)
            ->whereNotNull('pillar')
            ->get()
            ->countBy(fn (ContentPost $post): string => $post->pillar->value);

        $total = $counts->sum();

        return collect(ContentPillar::orderedCases())
            ->map(function (ContentPillar $pillar) use ($counts, $total): array {
                $count = (int) $counts->get($pillar->value, 0);

                return [
                    'pillar' => $pillar,
                    'label' => $pillar->getLabel(),
                    'color' => $pillar->getHexColor(),
                    'count' => $count,
                    'percentage' => $total > 0 ? round($count * 100 / $total, 1) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Resumen numérico de la cabecera del panel.
     *
     * @param  BoardFilters  $filters
     * @return array{total: int, blocked: int, urgent: int, scheduled: int, overdue: int}
     */
    public function summary(array $filters): array
    {
        $posts = $this->query($filters)->get();

        return [
            'total' => $posts->count(),
            'blocked' => $posts->filter(fn (ContentPost $post): bool => $post->isBlocked())->count(),
            'urgent' => $posts->filter(fn (ContentPost $post): bool => $post->priority->weight() >= 3)->count(),
            'scheduled' => $posts->filter(fn (ContentPost $post): bool => $post->status === ContentPostStatus::Scheduled)->count(),
            'overdue' => $posts->filter(fn (ContentPost $post): bool => $post->isOverdue())->count(),
        ];
    }

    /**
     * @param  BoardFilters  $filters
     * @return Collection<string, Collection<int, ContentPost>>
     */
    protected function daySummaries(array $filters, Carbon $from, Carbon $to): Collection
    {
        return $this->query($filters)
            ->scheduledBetween($from->copy()->startOfDay(), $to->copy()->endOfDay())
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (ContentPost $post): string => $post->scheduled_at
                ->timezone(config('app.timezone'))
                ->format('Y-m-d'));
    }

    /**
     * @param  Collection<string, Collection<int, ContentPost>>  $summaries
     * @return array{date: string, day: int, isCurrentMonth: bool, isToday: bool, isSelected: bool, count: int, workload: string, posts: list<array<string, mixed>>}
     */
    protected function dayCell(Carbon $cursor, Collection $summaries, string $selectedDate, bool $isCurrentMonth): array
    {
        $dateKey = $cursor->format('Y-m-d');
        /** @var Collection<int, ContentPost> $posts */
        $posts = $summaries->get($dateKey, collect());

        return [
            'date' => $dateKey,
            'day' => $cursor->day,
            'isCurrentMonth' => $isCurrentMonth,
            'isToday' => $cursor->isToday(),
            'isSelected' => $dateKey === $selectedDate,
            'count' => $posts->count(),
            'workload' => self::workloadLevel($posts->count()),
            'posts' => $posts->map(fn (ContentPost $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'brand' => $post->brand?->name,
                'brandColor' => $post->brand?->color_hex,
                'time' => $post->scheduled_at?->timezone(config('app.timezone'))->format('H:i'),
                'status' => $post->status->value,
                'statusLabel' => $post->status->getLabel(),
                'statusColor' => $post->status->getHexColor(),
                'priority' => $post->priority->value,
                'priorityLabel' => $post->priority->getLabel(),
                'format' => $post->format->getLabel(),
                'blocked' => $post->isBlocked(),
                'blockerLabel' => $post->blocker->getLabel(),
            ])->values()->all(),
        ];
    }
}
