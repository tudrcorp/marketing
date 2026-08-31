<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\CorporateEvent;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class CorporateEventPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewCorporateEvents);
    }

    public function view(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ViewCorporateEvents);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }

    public function update(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents)
            && $corporateEvent->statusEnum()->isEditable();
    }

    public function delete(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }

    public function restore(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }

    public function forceDelete(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }

    public function promote(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }

    public function publish(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }

    public function manageRegistrations(User $user, CorporateEvent $corporateEvent): bool
    {
        return $this->allows($user, MarketingPermission::ManageCorporateEvents);
    }
}
