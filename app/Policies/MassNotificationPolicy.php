<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\MassNotification;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class MassNotificationPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewMassNotifications);
    }

    public function view(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ViewMassNotifications);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }

    public function send(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }

    public function sendTest(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }

    public function update(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }

    public function delete(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }

    public function restore(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }

    public function forceDelete(User $user, MassNotification $massNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageMassNotifications);
    }
}
