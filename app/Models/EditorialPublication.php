<?php

namespace App\Models;

use App\Marketing\PublicationStatus;
use Database\Factories\EditorialPublicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'social_account_id',
    'title',
    'body',
    'reference_image',
    'media_urls',
    'hashtags',
    'scheduled_at',
    'published_at',
    'status',
    'approval_notes',
    'approved_by_id',
    'approved_at',
    'created_by_id',
])]
class EditorialPublication extends Model
{
    /** @use HasFactory<EditorialPublicationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'media_urls' => 'array',
            'hashtags' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
