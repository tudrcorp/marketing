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
                description: 'Grupos de clientes y contactos para campañas.',
                icon: 'heroicon-o-user-group',
                permissions: [
                    self::ViewClientGroups,
                    self::ManageClientGroups,
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
