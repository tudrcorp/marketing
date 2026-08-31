<?php

namespace App\Models\Concerns;

use App\Models\MarketingRole;

trait HasMarketingPermissions
{
    public function hasMarketingPermission(string $permission): bool
    {
        $role = $this->marketingRole;

        if (! $role instanceof MarketingRole) {
            return false;
        }

        return $role->hasPermission($permission);
    }

    public function canManageMarketingOperations(): bool
    {
        return $this->marketingRole !== null;
    }

    public function isMarketingAdministrator(): bool
    {
        return $this->marketingRole?->slug === 'administrador';
    }

    public function isLastMarketingAdministrator(): bool
    {
        if (! $this->isMarketingAdministrator()) {
            return false;
        }

        return static::query()
            ->whereKeyNot($this->getKey())
            ->whereHas('marketingRole', function ($query): void {
                $query->where('slug', 'administrador');
            })
            ->doesntExist();
    }
}
