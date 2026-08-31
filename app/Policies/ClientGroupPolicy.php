<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\ClientGroup;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class ClientGroupPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewClientGroups);
    }

    public function view(User $user, ClientGroup $clientGroup): bool
    {
        return $this->allows($user, MarketingPermission::ViewClientGroups);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageClientGroups);
    }

    public function update(User $user, ClientGroup $clientGroup): bool
    {
        return $this->allows($user, MarketingPermission::ManageClientGroups);
    }

    public function delete(User $user, ClientGroup $clientGroup): bool
    {
        return $this->allows($user, MarketingPermission::ManageClientGroups);
    }

    public function restore(User $user, ClientGroup $clientGroup): bool
    {
        return $this->allows($user, MarketingPermission::ManageClientGroups);
    }

    public function forceDelete(User $user, ClientGroup $clientGroup): bool
    {
        return $this->allows($user, MarketingPermission::ManageClientGroups);
    }

    public function manageClients(User $user, ClientGroup $clientGroup): bool
    {
        return $this->allows($user, MarketingPermission::ManageClientGroups);
    }
}
