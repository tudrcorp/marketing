<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'color_hex',
    'brand_voice',
    'drive_url',
    'canva_url',
    'ctas',
    'hashtag_groups',
    'quick_links',
    'is_active',
    'created_by_id',
])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ctas' => 'array',
            'hashtag_groups' => 'array',
            'quick_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $brand): void {
            if (blank($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    /**
     * @return HasMany<ContentPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(ContentPost::class);
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Iniciales usadas por el avatar de la marca en el tablero.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function vaultCtas(): array
    {
        return $this->normalizeVaultEntries($this->ctas);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function vaultHashtagGroups(): array
    {
        return $this->normalizeVaultEntries($this->hashtag_groups);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function vaultQuickLinks(): array
    {
        return $this->normalizeVaultEntries($this->quick_links);
    }

    /**
     * Acepta tanto pares etiqueta/valor como listas planas de texto.
     *
     * @param  array<int|string, mixed>|null  $entries
     * @return list<array{label: string, value: string}>
     */
    protected function normalizeVaultEntries(?array $entries): array
    {
        return collect($entries ?? [])
            ->map(function (mixed $entry, int|string $key): ?array {
                if (is_array($entry)) {
                    $label = (string) ($entry['label'] ?? $key);
                    $value = (string) ($entry['value'] ?? '');
                } else {
                    $label = is_string($key) ? $key : (string) $entry;
                    $value = (string) $entry;
                }

                return blank($value) ? null : ['label' => $label, 'value' => $value];
            })
            ->filter()
            ->values()
            ->all();
    }
}
