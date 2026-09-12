<?php

namespace App\Policies;

use App\Marketing\MarketingPermission;
use App\Models\ContentPost;
use App\Models\User;
use App\Policies\Concerns\InteractsWithMarketingPermissions;

class ContentPostPolicy
{
    use InteractsWithMarketingPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ViewContentPosts);
    }

    public function view(User $user, ContentPost $contentPost): bool
    {
        return $this->allows($user, MarketingPermission::ViewContentPosts);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, MarketingPermission::ManageContentPosts);
    }

    public function update(User $user, ContentPost $contentPost): bool
    {
        return $this->allows($user, MarketingPermission::ManageContentPosts);
    }

    public function delete(User $user, ContentPost $contentPost): bool
    {
        return $this->allows($user, MarketingPermission::ManageContentPosts);
    }

    public function restore(User $user, ContentPost $contentPost): bool
    {
        return $this->allows($user, MarketingPermission::ManageContentPosts);
    }

    public function forceDelete(User $user, ContentPost $contentPost): bool
    {
        return $this->allows($user, MarketingPermission::ManageContentPosts);
    }
}
