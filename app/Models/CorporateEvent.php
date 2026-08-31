<?php

namespace App\Models;

use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventStatus;
use App\Marketing\CorporateEventType;
use Database\Factories\CorporateEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'title',
    'code',
    'event_type',
    'modality',
    'status',
    'summary',
    'description',
    'starts_at',
    'ends_at',
    'venue_name',
    'venue_address',
    'virtual_url',
    'cover_image',
    'attachments',
    'target_audiences',
    'capacity',
    'registrations_count',
    'registration_url',
    'registration_token',
    'registration_deadline',
    'promoted_channels',
    'mass_notification_id',
    'created_by_id',
    'published_at',
    'promoted_at',
    'cancelled_at',
])]
class CorporateEvent extends Model
{
    /** @use HasFactory<CorporateEventFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_deadline' => 'datetime',
            'published_at' => 'datetime',
            'promoted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attachments' => 'array',
            'target_audiences' => 'array',
            'promoted_channels' => 'array',
            'capacity' => 'integer',
            'registrations_count' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function massNotification(): BelongsTo
    {
        return $this->belongsTo(MassNotification::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CorporateEventRegistration::class);
    }

    public function typeEnum(): ?CorporateEventType
    {
        return CorporateEventType::tryFrom((string) $this->event_type);
    }

    public function modalityEnum(): ?CorporateEventModality
    {
        return CorporateEventModality::tryFrom((string) $this->modality);
    }

    public function statusEnum(): CorporateEventStatus
    {
        return CorporateEventStatus::tryFrom((string) $this->status) ?? CorporateEventStatus::Draft;
    }

    public function hasCapacity(): bool
    {
        return filled($this->capacity);
    }

    public function isFull(): bool
    {
        return $this->hasCapacity() && $this->registrations_count >= $this->capacity;
    }

    public function remainingCapacity(): ?int
    {
        if (! $this->hasCapacity()) {
            return null;
        }

        return max(0, $this->capacity - $this->registrations_count);
    }

    public function attendanceRate(): ?float
    {
        if ($this->registrations_count === 0) {
            return null;
        }

        $attended = $this->registrations()
            ->where('status', 'attended')
            ->count();

        return round(($attended / $this->registrations_count) * 100, 1);
    }

    /**
     * @param  Builder<CorporateEvent>  $query
     * @return Builder<CorporateEvent>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->whereNotIn('status', [
                CorporateEventStatus::Cancelled->value,
                CorporateEventStatus::Completed->value,
            ])
            ->where(function (Builder $builder): void {
                $builder
                    ->where('starts_at', '>=', now()->startOfDay())
                    ->orWhere('ends_at', '>=', now());
            })
            ->orderBy('starts_at');
    }

    /**
     * @param  Builder<CorporateEvent>  $query
     * @return Builder<CorporateEvent>
     */
    public function scopeInMonth(Builder $query, Carbon $month): Builder
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $query->whereBetween('starts_at', [$start, $end]);
    }
}
