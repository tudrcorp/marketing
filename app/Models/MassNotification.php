<?php

namespace App\Models;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MassNotificationContentType;
use Database\Factories\MassNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title',
    'copy',
    'channels',
    'audiences',
    'recipient_ids',
    'content_type',
    'attachment',
    'created_by_id',
])]
class MassNotification extends Model
{
    /** @use HasFactory<MassNotificationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audiences' => 'array',
            'recipient_ids' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function dispatchLogs(): HasMany
    {
        return $this->hasMany(NotificationDispatchLog::class)->latest('logged_at');
    }

    public function attachmentPublicUrl(): ?string
    {
        if (! filled($this->attachment) || ! Storage::disk('public')->exists($this->attachment)) {
            return null;
        }

        $url = Storage::disk('public')->url($this->attachment);

        if (app()->environment('local') && str_contains($url, '.test')) {
            return str_replace('https://', 'http://', $url);
        }

        return $url;
    }

    public function contentTypeEnum(): ?MassNotificationContentType
    {
        return MassNotificationContentType::tryFromMixed($this->content_type);
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
