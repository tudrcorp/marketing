<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class MarketingRolePolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function view(User $user, MarketingRole $marketingRole): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function update(User $user, MarketingRole $marketingRole): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function delete(User $user, MarketingRole $marketingRole): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles) && ! $marketingRole->is_system;
    }
}
