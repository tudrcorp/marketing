<?php

namespace App\Models;

use App\Marketing\SocialPlatform;
use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'platform',
    'handle',
    'account_password',
    'profile_url',
    'is_active',
    'notes',
    'created_by_id',
])]
#[Hidden(['account_password'])]
class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'account_password' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function hasAccountPassword(): bool
    {
        return filled($this->getRawOriginal('account_password'));
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(EditorialPublication::class);
    }
}
