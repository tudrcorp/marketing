<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\Brand;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class BrandPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewBrands);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $this->allows($user, MarketingPermission::ViewBrands);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageBrands);
    }

    public function update(User $user, Brand $brand): bool
    {
        return $this->allows($user, MarketingPermission::ManageBrands);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->allows($user, MarketingPermission::ManageBrands);
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $this->allows($user, MarketingPermission::ManageBrands);
    }

    public function forceDelete(User $user, Brand $brand): bool
    {
        return $this->allows($user, MarketingPermission::ManageBrands);
    }
}
