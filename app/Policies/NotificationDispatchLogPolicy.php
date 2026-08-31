<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class NotificationDispatchLogPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewNotificationLogs);
    }

    public function view(User $user, NotificationDispatchLog $notificationDispatchLog): bool
    {
        return $this->allows($user, MarketingPermission::ViewNotificationLogs);
    }

    public function retry(User $user, NotificationDispatchLog $notificationDispatchLog): bool
    {
        return $this->allows($user, MarketingPermission::ViewNotificationLogs)
            && $this->allows($user, MarketingPermission::ManageMassNotifications);
    }
}
