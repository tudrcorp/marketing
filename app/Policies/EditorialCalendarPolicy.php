<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class EditorialCalendarPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewCalendar);
    }
}
