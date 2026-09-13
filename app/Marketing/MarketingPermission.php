<?php

namespace App\Marketing;

class MarketingPermission
{
    public const ViewSocialAccounts = 'social_accounts.view';

    public const ManageSocialAccounts = 'social_accounts.manage';

    public const ViewPublications = 'publications.view';

    public const ManagePublications = 'publications.manage';

    public const ApprovePublications = 'publications.approve';

    public const ViewCalendar = 'calendar.view';

    public const ManageRoles = 'roles.manage';

    public const ViewBirthdayNotifications = 'birthday_notifications.view';

    public const ManageBirthdayNotifications = 'birthday_notifications.manage';

    public const ViewMassNotifications = 'mass_notifications.view';

    public const ManageMassNotifications = 'mass_notifications.manage';

    public const ViewCorporateEvents = 'corporate_events.view';

    public const ManageCorporateEvents = 'corporate_events.manage';

    public const ViewNotificationLogs = 'notification_logs.view';

    public const ViewClientGroups = 'client_groups.view';

    public const ManageClientGroups = 'client_groups.manage';

    public const ViewExternalCompanies = 'external_companies.view';

    public const ManageExternalCompanies = 'external_companies.manage';

    public const ViewBrands = 'content_brands.view';

    public const ManageBrands = 'content_brands.manage';

    public const ViewContentPosts = 'content_posts.view';

    public const ManageContentPosts = 'content_posts.manage';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ViewSocialAccounts => 'Ver cuentas de redes',
            self::ManageSocialAccounts => 'Administrar cuentas de redes',
            self::ViewPublications => 'Ver publicaciones',
            self::ManagePublications => 'Administrar publicaciones',
            self::ApprovePublications => 'Aprobar publicaciones',
            self::ViewCalendar => 'Ver calendario editorial',
            self::ManageRoles => 'Administrar roles',
            self::ViewBirthdayNotifications => 'Ver notificaciones de cumpleaños',
            self::ManageBirthdayNotifications => 'Administrar notificaciones de cumpleaños',
            self::ViewMassNotifications => 'Ver notificaciones masivas',
            self::ManageMassNotifications => 'Administrar notificaciones masivas',
            self::ViewCorporateEvents => 'Ver eventos corporativos',
            self::ManageCorporateEvents => 'Administrar eventos corporativos',
            self::ViewNotificationLogs => 'Ver historial de envíos y fallas',
            self::ViewClientGroups => 'Ver grupos de clientes',
            self::ManageClientGroups => 'Administrar grupos de clientes',
            self::ViewExternalCompanies => 'Ver externos',
            self::ManageExternalCompanies => 'Administrar externos',
            self::ViewBrands => 'Ver marcas',
            self::ManageBrands => 'Administrar marcas',
            self::ViewContentPosts => 'Ver tablero de contenido',
            self::ManageContentPosts => 'Administrar contenido multimarca',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::ViewSocialAccounts => 'Consultar perfiles conectados de la marca.',
            self::ManageSocialAccounts => 'Crear, editar y desactivar cuentas de redes.',
            self::ViewPublications => 'Revisar publicaciones editoriales programadas.',
            self::ManagePublications => 'Crear y editar publicaciones del calendario.',
            self::ApprovePublications => 'Aprobar o rechazar contenido antes de publicarse.',
            self::ViewCalendar => 'Acceder al calendario editorial mensual.',
            self::ManageRoles => 'Gestionar perfiles y permisos del equipo.',
            self::ViewBirthdayNotifications => 'Consultar campañas de cumpleaños.',
            self::ManageBirthdayNotifications => 'Configurar y enviar felicitaciones automáticas.',
            self::ViewMassNotifications => 'Consultar envíos masivos por WhatsApp, SMS o email.',
            self::ManageMassNotifications => 'Crear y ejecutar campañas masivas.',
            self::ViewCorporateEvents => 'Consultar eventos TDG y su calendario.',
            self::ManageCorporateEvents => 'Crear, editar y promocionar eventos corporativos.',
            self::ViewNotificationLogs => 'Auditar entregas, fallos y trazas del API.',
            self::ViewClientGroups => 'Consultar grupos de clientes y responsables.',
            self::ManageClientGroups => 'Registrar clientes y administrar audiencias.',
            self::ViewExternalCompanies => 'Consultar empresas externas y sus responsables.',
            self::ManageExternalCompanies => 'Registrar y editar empresas externas para campañas.',
            self::ViewBrands => 'Consultar la ficha y los recursos fijos de cada marca.',
            self::ManageBrands => 'Crear y editar marcas, bóveda de CTAs, hashtags y enlaces.',
            self::ViewContentPosts => 'Acceder al panel máster, Kanban y calendario de contenido.',
            self::ManageContentPosts => 'Crear, mover y reprogramar piezas de contenido.',
        ];
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     icon: string,
     *     permissions: list<string>,
     * }>
     */
    public static function groups(): array
    {
        $labels = self::labels();
        $descriptions = self::descriptions();

        $buildGroup = fn (string $key, string $label, string $description, string $icon, array $permissions): array => [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'icon' => $icon,
            'permissions' => array_map(
                fn (string $permission): array => [
                    'key' => $permission,
                    'label' => $labels[$permission],
                    'description' => $descriptions[$permission],
                ],
                $permissions,
            ),
        ];

        return [
            $buildGroup(
                key: 'editorial',
                label: 'Editorial y redes',
                description: 'Cuentas sociales, publicaciones y calendario editorial.',
                icon: 'heroicon-o-megaphone',
                permissions: [
                    self::ViewSocialAccounts,
                    self::ManageSocialAccounts,
                    self::ViewPublications,
                    self::ManagePublications,
                    self::ApprovePublications,
                    self::ViewCalendar,
                ],
            ),
            $buildGroup(
                key: 'notifications',
                label: 'Notificaciones',
                description: 'Cumpleaños, envíos masivos e historial de entregas.',
                icon: 'heroicon-o-bell-alert',
                permissions: [
                    self::ViewBirthdayNotifications,
                    self::ManageBirthdayNotifications,
                    self::ViewMassNotifications,
                    self::ManageMassNotifications,
                    self::ViewNotificationLogs,
                ],
            ),
            $buildGroup(
                key: 'events',
                label: 'Eventos corporativos',
                description: 'Agenda TDG, inscripciones y promoción de actividades.',
                icon: 'heroicon-o-calendar-days',
                permissions: [
                    self::ViewCorporateEvents,
                    self::ManageCorporateEvents,
                ],
            ),
            $buildGroup(
                key: 'audiences',
                label: 'Audiencias TDG',
                description: 'Grupos de clientes, empresas externas y contactos para campañas.',
                icon: 'heroicon-o-user-group',
                permissions: [
                    self::ViewClientGroups,
                    self::ManageClientGroups,
                    self::ViewExternalCompanies,
                    self::ManageExternalCompanies,
                ],
            ),
            $buildGroup(
                key: 'content',
                label: 'Contenido multimarca',
                description: 'Marcas, tablero Kanban y calendario de producción de contenido.',
                icon: 'heroicon-o-squares-2x2',
                permissions: [
                    self::ViewBrands,
                    self::ManageBrands,
                    self::ViewContentPosts,
                    self::ManageContentPosts,
                ],
            ),
            $buildGroup(
                key: 'administration',
                label: 'Administración',
                description: 'Control de accesos y configuración del panel.',
                icon: 'heroicon-o-shield-check',
                permissions: [
                    self::ManageRoles,
                ],
            ),
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @param  list<string>|array<int, string>  $permissions
     * @return list<string>
     */
    public static function sanitize(array $permissions): array
    {
        return array_values(array_intersect($permissions, self::all()));
    }
}
