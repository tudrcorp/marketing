<?php

namespace App\Models;

use App\Marketing\ContentPillar;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostFormat;
use App\Marketing\ContentPostPriority;
use App\Marketing\ContentPostStatus;
use Database\Factories\ContentPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'brand_id',
    'title',
    'copy',
    'status',
    'priority',
    'blocker',
    'format',
    'pillar',
    'scheduled_at',
    'published_at',
    'time_spent_seconds',
    'timer_started_at',
    'is_replicable',
    'is_evergreen',
    'reference_image',
    'board_position',
    'feed_position',
    'created_by_id',
])]
class ContentPost extends Model
{
    /** @use HasFactory<ContentPostFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentPostStatus::class,
            'priority' => ContentPostPriority::class,
            'blocker' => ContentPostBlocker::class,
            'format' => ContentPostFormat::class,
            'pillar' => ContentPillar::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'timer_started_at' => 'datetime',
            'is_replicable' => 'boolean',
            'is_evergreen' => 'boolean',
            'time_spent_seconds' => 'integer',
            'board_position' => 'integer',
            'feed_position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForBrand(Builder $query, ?int $brandId): Builder
    {
        return $query->when($brandId, fn (Builder $builder): Builder => $builder->where('brand_id', $brandId));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('blocker', '!=', ContentPostBlocker::None->value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeScheduledBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereNotNull('scheduled_at')->whereBetween('scheduled_at', [$from, $to]);
    }

    /**
     * Ordena por urgencia real: primero prioridad, luego la fecha de publicación más cercana.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrderByUrgency(Builder $query): Builder
    {
        $cases = collect(ContentPostPriority::cases())
            ->map(fn (ContentPostPriority $priority): string => sprintf(
                "WHEN '%s' THEN %d",
                $priority->value,
                $priority->weight(),
            ))
            ->implode(' ');

        return $query
            ->orderByRaw("CASE priority {$cases} ELSE 0 END DESC")
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->orderBy('board_position');
    }

    public function isTimerRunning(): bool
    {
        return $this->timer_started_at !== null;
    }

    /**
     * Tiempo acumulado incluyendo el tramo en curso si el temporizador está activo.
     */
    public function elapsedSeconds(): int
    {
        if (! $this->isTimerRunning()) {
            return $this->time_spent_seconds;
        }

        return $this->time_spent_seconds + max(0, $this->timer_started_at->diffInSeconds(now()));
    }

    public function formattedElapsedTime(): string
    {
        $seconds = $this->elapsedSeconds();

        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    public function isBlocked(): bool
    {
        return $this->blocker instanceof ContentPostBlocker && $this->blocker->isBlocking();
    }

    public function isOverdue(): bool
    {
        return $this->scheduled_at !== null
            && $this->status !== ContentPostStatus::Published
            && $this->scheduled_at->isPast();
    }
}
