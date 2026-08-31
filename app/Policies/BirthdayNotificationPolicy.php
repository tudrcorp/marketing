<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\BirthdayNotification;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class BirthdayNotificationPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewBirthdayNotifications);
    }

    public function view(User $user, BirthdayNotification $birthdayNotification): bool
    {
        return $this->allows($user, MarketingPermission::ViewBirthdayNotifications);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageBirthdayNotifications);
    }

    public function update(User $user, BirthdayNotification $birthdayNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageBirthdayNotifications);
    }

    public function sendTest(User $user, BirthdayNotification $birthdayNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageBirthdayNotifications);
    }

    public function delete(User $user, BirthdayNotification $birthdayNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageBirthdayNotifications);
    }

    public function restore(User $user, BirthdayNotification $birthdayNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageBirthdayNotifications);
    }

    public function forceDelete(User $user, BirthdayNotification $birthdayNotification): bool
    {
        return $this->allows($user, MarketingPermission::ManageBirthdayNotifications);
    }
}
