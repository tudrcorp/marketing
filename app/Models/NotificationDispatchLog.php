<?php

namespace App\Models;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use Database\Factories\NotificationDispatchLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source',
    'status',
    'channel',
    'title',
    'summary',
    'failure_code',
    'analyst_message',
    'resolution_steps',
    'sent_count',
    'total_count',
    'recipient',
    'batch_number',
    'total_batches',
    'birthday_notification_id',
    'mass_notification_id',
    'corporate_event_id',
    'sent_by_id',
    'technical_detail',
    'logged_at',
])]
class NotificationDispatchLog extends Model
{
    /** @use HasFactory<NotificationDispatchLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technical_detail' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function sourceEnum(): NotificationDispatchSource
    {
        return NotificationDispatchSource::from($this->source);
    }

    public function statusEnum(): NotificationDispatchStatus
    {
        return NotificationDispatchStatus::from($this->status);
    }

    public function channelEnum(): ?BirthdayNotificationChannel
    {
        if (blank($this->channel)) {
            return null;
        }

        return BirthdayNotificationChannel::tryFrom($this->channel);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }

    public function birthdayNotification(): BelongsTo
    {
        return $this->belongsTo(BirthdayNotification::class);
    }

    public function massNotification(): BelongsTo
    {
        return $this->belongsTo(MassNotification::class);
    }

    public function corporateEvent(): BelongsTo
    {
        return $this->belongsTo(CorporateEvent::class);
    }

    public function requiresAttention(): bool
    {
        return $this->statusEnum()->isActionable();
    }

    public function deliveryRatio(): ?float
    {
        if ($this->total_count === 0) {
            return null;
        }

        return round(($this->sent_count / $this->total_count) * 100, 1);
    }
}
