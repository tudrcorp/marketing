<?php

namespace App\Models;

use App\Marketing\MarketingPermission;
use Database\Factories\MarketingRoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'permissions', 'is_system'])]
class MarketingRole extends Model
{
    /** @use HasFactory<MarketingRoleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function grantsAllPermissions(): bool
    {
        return $this->hasPermission(MarketingPermission::ManageRoles)
            && count($this->permissions ?? []) >= count(MarketingPermission::all());
    }
}
