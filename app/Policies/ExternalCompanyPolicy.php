<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\ExternalCompany;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class ExternalCompanyPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewExternalCompanies);
    }

    public function view(User $user, ExternalCompany $externalCompany): bool
    {
        return $this->allows($user, MarketingPermission::ViewExternalCompanies);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageExternalCompanies);
    }

    public function update(User $user, ExternalCompany $externalCompany): bool
    {
        return $this->allows($user, MarketingPermission::ManageExternalCompanies);
    }

    public function delete(User $user, ExternalCompany $externalCompany): bool
    {
        return $this->allows($user, MarketingPermission::ManageExternalCompanies);
    }

    public function restore(User $user, ExternalCompany $externalCompany): bool
    {
        return $this->allows($user, MarketingPermission::ManageExternalCompanies);
    }

    public function forceDelete(User $user, ExternalCompany $externalCompany): bool
    {
        return $this->allows($user, MarketingPermission::ManageExternalCompanies);
    }
}
