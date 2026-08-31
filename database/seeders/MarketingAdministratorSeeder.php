<?php

namespace Database\Seeders;

use App\Models\MarketingRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarketingAdministratorSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'gcamacho@tudrencasa.com';

    public function run(): void
    {
        $this->call(MarketingRoleSeeder::class);

        $adminRole = MarketingRole::query()
            ->where('slug', 'administrador')
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Gustavo Camacho',
                'email_verified_at' => now(),
                'password' => env('MARKETING_ADMIN_PASSWORD', 'password'),
                'marketing_role_id' => $adminRole->id,
            ],
        );
    }
}
