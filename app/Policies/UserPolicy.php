<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class UserPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function view(User $user, User $model): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function update(User $user, User $model): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function delete(User $user, User $model): bool
    {
        if (! $this->allows($user, MarketingPermission::ManageRoles)) {
            return false;
        }

        if ($user->is($model)) {
            return false;
        }

        return ! $model->isLastMarketingAdministrator();
    }

    public function restore(User $user, User $model): bool
    {
        return $this->allows($user, MarketingPermission::ManageRoles);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }
}
