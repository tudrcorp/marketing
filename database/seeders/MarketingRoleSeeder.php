<?php

namespace Database\Seeders;

use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use Illuminate\Database\Seeder;

class MarketingRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador de marketing',
                'slug' => 'administrador',
                'description' => 'Acceso completo a operaciones, aprobaciones y configuración de roles.',
                'permissions' => MarketingPermission::all(),
                'is_system' => true,
            ],
            [
                'name' => 'Analista de marketing',
                'slug' => 'analista',
                'description' => 'Crea y administra publicaciones y cuentas de redes.',
                'permissions' => [
                    MarketingPermission::ViewSocialAccounts,
                    MarketingPermission::ManageSocialAccounts,
                    MarketingPermission::ViewPublications,
                    MarketingPermission::ManagePublications,
                    MarketingPermission::ViewCalendar,
                    MarketingPermission::ViewBirthdayNotifications,
                    MarketingPermission::ManageBirthdayNotifications,
                    MarketingPermission::ViewMassNotifications,
                    MarketingPermission::ManageMassNotifications,
                    MarketingPermission::ViewCorporateEvents,
                    MarketingPermission::ManageCorporateEvents,
                    MarketingPermission::ViewNotificationLogs,
                    MarketingPermission::ViewClientGroups,
                    MarketingPermission::ManageClientGroups,
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Aprobador editorial',
                'slug' => 'aprobador',
                'description' => 'Valida y aprueba el cronograma de publicaciones.',
                'permissions' => [
                    MarketingPermission::ViewSocialAccounts,
                    MarketingPermission::ViewPublications,
                    MarketingPermission::ApprovePublications,
                    MarketingPermission::ViewCalendar,
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Visor autorizado',
                'slug' => 'visor',
                'description' => 'Consulta el calendario y publicaciones sin permisos de edición.',
                'permissions' => [
                    MarketingPermission::ViewSocialAccounts,
                    MarketingPermission::ViewPublications,
                    MarketingPermission::ViewCalendar,
                ],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            MarketingRole::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role,
            );
        }
    }
}
