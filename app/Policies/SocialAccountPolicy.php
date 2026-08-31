<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\SocialAccount;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class SocialAccountPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewSocialAccounts);
    }

    public function view(User $user, SocialAccount $socialAccount): bool
    {
        return $this->allows($user, MarketingPermission::ViewSocialAccounts);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageSocialAccounts);
    }

    public function update(User $user, SocialAccount $socialAccount): bool
    {
        return $this->allows($user, MarketingPermission::ManageSocialAccounts);
    }

    public function delete(User $user, SocialAccount $socialAccount): bool
    {
        return $this->allows($user, MarketingPermission::ManageSocialAccounts);
    }

    public function manageCredentials(User $user, SocialAccount $socialAccount): bool
    {
        return $user->isMarketingAdministrator();
    }

    public function viewPassword(User $user, SocialAccount $socialAccount): bool
    {
        return $user->isMarketingAdministrator()
            && $socialAccount->hasAccountPassword();
    }
}
