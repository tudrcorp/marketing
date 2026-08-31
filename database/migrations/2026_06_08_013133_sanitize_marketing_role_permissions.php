<?php

use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MarketingRole::query()->each(function (MarketingRole $role): void {
            $sanitized = MarketingPermission::sanitize($role->permissions ?? []);

            if ($sanitized === ($role->permissions ?? [])) {
                return;
            }

            $role->update(['permissions' => $sanitized]);
        });
    }

    public function down(): void
    {
        //
    }
};
