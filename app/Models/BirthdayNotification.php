<?php

namespace App\Models;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use Database\Factories\BirthdayNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'image',
    'copy',
    'channels',
    'audiences',
    'created_by_id',
])]
class BirthdayNotification extends Model
{
    /** @use HasFactory<BirthdayNotificationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audiences' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return list<BirthdayNotificationChannel>
     */
    public function channelEnums(): array
    {
        return collect($this->channels ?? [])
            ->map(fn (string $channel): ?BirthdayNotificationChannel => BirthdayNotificationChannel::tryFrom($channel))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<BirthdayNotificationAudience>
     */
    public function audienceEnums(): array
    {
        return collect($this->audiences ?? [])
            ->map(fn (string $audience): ?BirthdayNotificationAudience => BirthdayNotificationAudience::tryFrom($audience))
            ->filter()
            ->values()
            ->all();
    }
}
