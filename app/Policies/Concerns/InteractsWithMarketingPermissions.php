<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait InteractsWithMarketingPermissions
{
    protected function allows(User $user, string $permission): bool
    {
        return $user->hasMarketingPermission($permission);
    }
}
