<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\EditorialPublication;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class EditorialPublicationPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewPublications);
    }

    public function view(User $user, EditorialPublication $editorialPublication): bool
    {
        return $this->allows($user, MarketingPermission::ViewPublications);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManagePublications);
    }

    public function update(User $user, EditorialPublication $editorialPublication): bool
    {
        return $this->allows($user, MarketingPermission::ManagePublications);
    }

    public function delete(User $user, EditorialPublication $editorialPublication): bool
    {
        return $this->allows($user, MarketingPermission::ManagePublications);
    }

    public function approve(User $user, EditorialPublication $editorialPublication): bool
    {
        return $this->allows($user, MarketingPermission::ApprovePublications);
    }
}
