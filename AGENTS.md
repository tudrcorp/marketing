# CLAUDE.md

Guía de contexto para agentes de IA (Claude Code y similares) que trabajen en el repositorio **tdg-marketing**.

## 1. Idioma y reglas de comunicación

**Idioma obligatorio: español.** Pregunta, explica, comenta código (cuando se añadan comentarios) y responde siempre en español, sin excepción — mensajes al usuario, preguntas de aclaración, resúmenes de cambios, y texto de commits/PRs, salvo que el usuario pida explícitamente otro idioma.

**`AGENTS.md` es una copia byte a byte de este archivo** (para agentes que leen esa convención). Si editas uno, replica el cambio en el otro: `cp CLAUDE.md AGENTS.md`.

---

# BLOQUE A — Contexto de dominio: TDG Marketing

## 2. Qué es este proyecto y propósito de negocio

**tdg-marketing** es el panel interno de operaciones de marketing de **TDG**. No es un proveedor de API REST propia: es una aplicación web Laravel que:

- Expone un panel admin **Filament** (SPA) en `/marketing`.
- Expone una página pública de inscripción a eventos (Livewire) en `/inscripcion/{token}`.
- Usa **Fortify** (sesión, 2FA, passkeys) para autenticación de los usuarios del panel.
- **Consume** (no expone) un Marketing API externo (`integracorp-api`) configurado con `MARKETING_API_URL` (por defecto `http://localhost:4000`), autenticado con la cabecera `X-API-Key`.

URL local (Laravel Herd): `https://tdg-marketing.test`
Panel: `https://tdg-marketing.test/marketing`

## 3. Arquitectura

```
Navegador → HTML / Livewire / Filament + sesión Fortify → tdg-marketing (esta app)
                                                                  │
                                                     HTTP + X-API-Key
                                                                  ▼
                                          MARKETING_API_URL (integracorp-api, típ. :4000)
```

Reglas de arquitectura a respetar:

- Casi no hay controllers HTTP de dominio — `app/Http/Controllers` solo contiene la clase base `Controller`. La navegación se resuelve con rutas Livewire/Filament, no con controllers tradicionales.
- La lógica de negocio vive en `app/Services/Marketing/`.
- Enums de dominio y catálogo de permisos viven en `app/Marketing/`.
- La UI admin vive en `app/Filament/`.
- Los jobs de envío viven en `app/Jobs/`.
- **No existen** `app/Events` ni `app/Listeners` en este proyecto — no introduzcas ese patrón sin que el usuario lo pida.
- **No existe** `routes/api.php`. Esta app **no expone** API REST de negocio propia.
- Los modelos de audiencia externa son *API-backed*: se hidratan con `::fromApi()`, no tienen tabla, migración ni timestamps (ver sección 9).
- Los conceptos "campaigns" y "segments" existieron y **fueron eliminados históricamente** (migración `2026_06_07_231542_remove_campaigns_and_segments_from_marketing.php`). **No reintroducirlos** sin diseño explícito del usuario.

## 4. Stack y versiones

- PHP 8.4 en local, pero `composer.json` requiere `^8.3` — **no uses sintaxis que rompa en 8.3**. Laravel 13, Filament v5, Livewire v4, Flux UI v2, Fortify v1, Tailwind CSS v4, Pint (preset `laravel`), Pest v4.
- CI (`.github/workflows/tests.yml`) corre la suite en **PHP 8.3, 8.4 y 8.5** — no uses sintaxis exclusiva de 8.5 ni asumas solo 8.4. `.github/workflows/lint.yml` corre `composer lint` en 8.4. Ambos workflows disparan en `develop`, `main`, `master`, `workos`.
- Cola: `QUEUE_CONNECTION=database` (en local, con `php artisan queue:listen` vía `composer run dev`). Hay **dos colas**: `default` y `email` (ver sección 11).
- Base de datos por defecto (`.env.example`): SQLite (`DB_CONNECTION=sqlite`). En tests, siempre SQLite `:memory:` (ver sección 14).
- UI del panel: español, marca TDG (naranja/azul, tipografía IBM Plex Sans/Mono), Filament en modo SPA.

## 5. Mapa de directorios importantes

| Ruta | Contenido |
|---|---|
| `app/Filament/Resources/` | Recursos Filament (uno por entidad), cada uno con subcarpetas `Pages/`, `Schemas/`, `Tables/`, `Support/` |
| `app/Filament/Pages/` | Páginas Filament sueltas: `Dashboard`, `CorporateEventsCalendar`, `EditorialCalendar` |
| `app/Filament/Widgets/` | Widgets del panel: salud del API/BD (`ApiHealthWidget`, `DatabaseHealthWidget`, `MarketingDashboardHealthIndicatorsWidget`), heatmap de actividad y el floater de progreso de envíos |
| `app/Filament/Support/`, `app/Filament/Forms/Components/`, `app/Filament/Actions/` | Componentes de formulario/acciones compartidos entre recursos (`AudienceCheckboxList`, `ChannelToggleButtons`, `MassNotificationCampaignSchema`, `MarketingRolePermissionsPicker`, `SendMassNotificationBulkAction`) |
| `app/Filament/Auth/Pages/Login.php` | Página de login personalizada del panel |
| `app/Filament/Concerns/` | Traits reutilizables para recursos (`SetsMarketingAuthor`, `RestrictsSocialAccountCredentials`) |
| `app/Marketing/` | Enums de dominio (`PublicationStatus`, `BirthdayNotificationAudience`, canales, etc.) y `MarketingPermission` |
| `app/Services/Marketing/` | Toda la lógica de negocio e integración con el Marketing API externo |
| `app/Jobs/` | Jobs de envío/promoción asíncronos |
| `app/Mail/` | Renderers de HTML + adjuntos de los tres flujos de correo (cumpleaños, masivas, invitaciones de eventos) |
| `app/Support/` | Helpers de presentación puros y sin estado (`NamePresentation`, `DatePresentation`, `AffiliatePresentation`, `SupplierPresentation`, `BirthdayDate`) usados por tablas Filament y cubiertos por tests `tests/Unit/*PresentationTest.php` |
| `app/Models/` | Eloquent — mezcla de modelos persistidos y modelos *API-backed* |
| `app/Models/Concerns/HasMarketingPermissions.php` | Trait de `User` para verificar permisos de marketing |
| `app/Actions/Fortify/` | Acciones de Fortify (`CreateNewUser`, `ResetUserPassword`) — el resto de la autenticación es Fortify de serie |
| `app/Concerns/` | Reglas de validación compartidas de cuenta/usuario (`PasswordValidationRules`, `ProfileValidationRules`) — **no confundir** con `app/Filament/Concerns/` ni con `app/Models/Concerns/` |
| `app/Policies/` | Policies que delegan en `MarketingPermission` vía `InteractsWithMarketingPermissions` |
| `app/Providers/Filament/MarketingPanelProvider.php` | Único panel Filament de la app (id/path `marketing`) |
| `app/Livewire/CorporateEvents/PublicRegistration.php` | Formulario público de inscripción a eventos |
| `app/Console/Commands/SimulateBirthdayNotificationDispatchCommand.php` | Comando `birthday:simulate-dispatch` |
| `routes/web.php`, `routes/settings.php` | Únicas rutas HTTP de la app (no hay `routes/api.php`) |
| `tests/Concerns/SafeRefreshDatabase.php` | Guardia de seguridad para tests (ver sección 14) |

`app/Filament/Resources/MarketingCampaigns/` y `MarketingSegments/` existen pero están **vacías** — son residuo de la eliminación histórica de campaigns/segments (ver secciones 3 y 16). No las repuebles; bórralas solo si el usuario lo pide.

## 6. Dominios funcionales

1. **Notificaciones de cumpleaños** (`BirthdayNotification`) — campañas por audiencia y canal (email, WhatsApp, SMS); el cumpleaños del día se detecta paginando en vivo las mismas APIs externas de audiencias (**no hay endpoint dedicado de cumpleaños**); envío en lotes vía jobs (**sin disparador automático hoy**, ver sección 7).
2. **Notificaciones masivas** (`MassNotification`) — campañas a audiencias completas o destinatarios concretos, con adjunto opcional (`FileUpload` → disco `public`, carpeta `mass-notifications/attachments`) y progreso en vivo (floater).
3. **Eventos corporativos** (`CorporateEvent` / `CorporateEventRegistration`) — CRUD, publicación, promoción, inscripciones públicas por token, compartir enlace.
4. **Editorial / redes** (`SocialAccount`, `EditorialPublication`) — cuentas sociales oficiales TDG, calendario editorial, flujo de aprobación (`PublicationStatus`: `Draft` → `PendingApproval` → `Scheduled` → `Published`, con `Failed`/`Cancelled`).
5. **Audiencias TDG** — lectura (**sin persistencia local**) de agentes/agencias de corretaje, agentes/agencias de viajes, afiliados, proveedores y colaboradores RRHH, desde el Marketing API.
6. **Grupos de clientes** (`ClientGroup` / `Client`) — única audiencia con persistencia **local** en BD, con responsable (`responsible_name/email/phone`) y contactos.
7. **Historial de envíos** (`NotificationDispatchLog`) — auditoría de entregas/fallas con mensajes de remediación para analistas y opción de reintento.
8. **Roles y permisos** — RBAC propio (`MarketingRole` + `MarketingPermission`), sin Spatie ni paquete de terceros.
9. **Dashboard** — `ApiHealthWidget`, `DatabaseHealthWidget` y `MarketingDashboardHealthIndicatorsWidget` (los tres vía el trait `InteractsWithMarketingHealthCheck` sobre `MarketingApiHealthService`) más `MarketingActivityHeatmapWidget`, alimentado por `MarketingDashboardHeatmapService`, que cruza `CorporateEvent` + `EditorialPublication` **locales** por día (no consulta el API externo).

## 7. Catálogo detallado del Marketing API externo (endpoints salientes)

### Cliente y autenticación

- Factory HTTP: `App\Services\Marketing\MarketingApiHttpFactory` (métodos `client()` y `emailClient()`, con timeouts distintos).
- Config: `config/services.php` → clave `marketing_api`.
- Base URL: `env('MARKETING_API_URL', 'http://localhost:4000')`.
- Auth: cabecera `X-API-Key` con `MARKETING_API_KEY` (solo se añade si `filled($apiKey)`).
- `acceptJson()` en todas las peticiones.
- Timeouts configurables: general (`MARKETING_API_TIMEOUT`), batch (`MARKETING_API_BATCH_TIMEOUT`), email (`MARKETING_API_EMAIL_TIMEOUT`).

### Variables de entorno relevantes

```
MARKETING_API_URL
MARKETING_API_KEY
MARKETING_API_TIMEOUT
MARKETING_API_BATCH_TIMEOUT
MARKETING_API_EMAIL_TIMEOUT
MARKETING_API_BULK_EMAILS_PATH=/api/emails/bulk
MARKETING_API_MASS_SEND_BATCH_PATH=/api/notifications/mass/send-batch
MARKETING_API_BIRTHDAY_TEST_SEND_PATH=/api/notifications/birthday/test
MARKETING_API_MASS_SEND_PATH=/api/notifications/mass/send   # configurado en config/services.php pero NO se usa en ningún servicio/job de la app
MARKETING_BIRTHDAY_EMAIL_BATCH_SIZE=50
MARKETING_MASS_EMAIL_BATCH_SIZE=50
MARKETING_MASS_EMAIL_BATCH_PAUSE_SECONDS=5
MARKETING_MASS_EMAIL_QUOTA_COOLDOWN_MINUTES=60
MARKETING_MASS_EMAIL_AUTH_COOLDOWN_MINUTES=15
MARKETING_WHATSAPP_BATCH_SIZE=50
MARKETING_WHATSAPP_BATCH_PAUSE_SECONDS=15
MARKETING_ADMIN_PASSWORD   # usado solo por database/seeders/MarketingAdministratorSeeder.php
```

### Health

| Método | Path | Servicio |
|---|---|---|
| GET | `/api/health` | `MarketingApiHealthService::checkApi()` |
| GET | `/api/health/db` | `MarketingApiHealthService::checkDatabase()` |

### Audiencias (GET paginados)

Patrón de query estándar: `page`, `limit`, `search`, `sort`, `order`.
Respuesta típica: `{ success, data: [...], pagination: { page, limit, total, totalPages } }`.
Clase base abstracta: `MarketingPaginatedApiService` (`paginate()`/`find()`, falla en silencio a un paginador vacío ante `ConnectionException` o cualquier `Throwable`).

| Path | Servicio PHP | Modelo API-backed | Recurso Filament (slug) |
|---|---|---|---|
| `/api/agents` | `MarketingAgentsApiService` | `BrokerAgent` | `agentes-corretaje` |
| `/api/agencies` | `MarketingAgenciesApiService` | `BrokerAgency` | `agencias-corretaje` |
| `/api/travel-agents` | `MarketingTravelAgentsApiService` | `TravelAgent` | `agentes-viajes` |
| `/api/travel-agencies` | `MarketingTravelAgenciesApiService` | `TravelAgency` | `agencias-viajes` |
| `/api/affiliates` | `MarketingIndividualAffiliatesApiService` | `IndividualAffiliate` | `afiliados-individuales` |
| `/api/affiliate-corporates` | `MarketingCorporateAffiliatesApiService` | `CorporateAffiliate` | `afiliados-corporativos` |
| `/api/suppliers` | `MarketingLegalSuppliersApiService` | `LegalSupplier` | `proveedores-juridicos` |
| `/api/doctor-nurses` | `MarketingNaturalSuppliersApiService` | `NaturalSupplier` | `proveedores-naturales` |
| `/api/rrhh-colaboradores` | `MarketingRrhhColaboradoresApiService` | `RrhhColaborador` | `colaboradores` |

Nota RRHH: `MarketingRrhhColaboradoresApiService` es el único servicio que **no** hereda el `paginate()` genérico de la clase base — el API expone un tamaño de página fijo (10) e ignora `limit`/`search`, así que el servicio agrega (`once()`) todas las páginas localmente y luego pagina/filtra/ordena en memoria para que Filament pueda buscar y cambiar el tamaño de página con normalidad.

### Envío de emails masivos

- `POST {bulk_emails_path}` (config `bulk_emails_path`, por defecto `/api/emails/bulk`).
- Body real (`BirthdayNotificationBulkEmailService::sendBatch()` / `BirthdayNotificationTestSendService::sendEmail()` / `SendMassNotificationEmailBatchJob::handle()`): `recipients` (string — `json_encode()` de un array de emails), `copy` (HTML renderizado), `subject`, y opcionalmente `dry_run => 'true'`.
- Usado por **tres** flujos distintos, cada uno con su propio par renderer/adjuntos en `app/Mail/`: notificaciones de cumpleaños (envío real y de prueba, `BirthdayNotificationEmailRenderer` + `BirthdayNotificationEmailAttachments::attach()`), canal email de notificaciones masivas (`SendMassNotificationEmailBatchJob`, `MassNotificationEmailRenderer` + `MassNotificationEmailAttachments::attach()`) e invitaciones de eventos corporativos (`CorporateEventRegistrationShareService`, `CorporateEventInvitationEmailRenderer` + `CorporateEventInvitationEmailAttachments::attach()`). Los tres comparten el mismo patrón de payload y el mismo endpoint, pero no comparten clase.

### WhatsApp / pruebas

- `POST {birthday_test_send_path}` (por defecto `/api/notifications/birthday/test`) — prueba de canal WhatsApp/SMS. Body real: `channel`, `phone` (normalizado con `MarketingPhoneNormalizer`), `copy`, `image_url`, `notification_id`, `sent_by_id`, `is_test => true`.
- `POST {mass_send_batch_path}` (por defecto `/api/notifications/mass/send-batch`) — lotes de WhatsApp masivo. Body real (`SendMassNotificationWhatsAppBatchJob`): `channel`, `recipients` (array de teléfonos), `title`, `copy`, `attachment_url`, `attachment_base64`, `notification_id`, `batch_number`, `total_batches`, `sent_by_id`. Se acepta HTTP `200` o `202`; la respuesta incluye `accepted`/`success`, `queued` y un objeto `queue: { pending, processing, processed, failed }`.
- `MARKETING_API_MASS_SEND_PATH` (`/api/notifications/mass/send`) está definido en `config/services.php` pero **no se invoca** desde ningún servicio o job de esta app.
- `GET /api/notifications/whatsapp/status` solo aparece **mencionado como texto de ayuda** dentro de un mensaje de remediación en `NotificationDispatchFailureResolver` (para que el analista lo consulte manualmente en el API) — **no se llama** desde este repositorio.

### Jobs relacionados con el API

- `ProcessBirthdayNotificationJob` → recolecta destinatarios y encola lotes de `SendBirthdayEmailBatchJob`. **Ojo: hoy nada en la app lo despacha** — no hay scheduler (`routes/console.php` solo tiene `inspire`) ni acción en el panel que dispare el envío real de cumpleaños; el recurso Filament solo ofrece *envío de prueba* (`SendTestBirthdayNotificationAction`) y el comando `birthday:simulate-dispatch` corre con `dry_run`. Solo los tests instancian el job. Si el usuario pide "que los cumpleaños salgan solos", eso implica añadir el disparador (scheduler o acción), no arreglar el job.
- `SendBirthdayEmailBatchJob` → hace el `POST` de correos masivos de cumpleaños.
- `ProcessMassNotificationChannelsJob` → orquesta los canales de una notificación masiva.
- `SendMassNotificationEmailBatchJob` → hace el `POST` a `bulk_emails_path` para el canal email de una notificación masiva. Ante cuota agotada, bloqueo de autenticación o corte de red reprograma una copia con los destinatarios pendientes en vez de darlos por perdidos (ver sección 11).
- `SendMassNotificationWhatsAppBatchJob` → hace el `POST` a `send-batch`.
- `SendCorporateEventInvitationEmailJob` → envía la invitación de un evento corporativo por el mismo endpoint bulk (cola `email`).
- `PromoteCorporateEventJob` → promoción de eventos corporativos.
- `PersistNotificationDispatchLogJob` → persiste el log de despacho de forma asíncrona.

### Trazas de las llamadas al API (`MarketingApiTraceRecorder`)

Todo servicio o job que llame al Marketing API construye su traza con `MarketingApiTraceRecorder` (`fromResponse()` / `fromConnectionError()`), que devuelve un array `{ api_calls: [...] }` con endpoint, método, request, status y cuerpo de respuesta. Esa traza se guarda en el `NotificationDispatchLog` y es lo que alimenta los mensajes de remediación de `NotificationDispatchFailureResolver` y el reintento de `NotificationDispatchRetryService`. **Al añadir una llamada nueva al API desde un job/servicio, registra su traza con este recorder** en lugar de inventar un formato propio — si no, el historial de envíos queda sin diagnóstico. Lo usan hoy: `BirthdayNotificationBulkEmailService`, `BirthdayNotificationTestSendService`, `MassNotificationDispatchService`, `MassNotificationTestSendService`, `CorporateEventRegistrationShareService`, `SendMassNotificationEmailBatchJob`, `SendMassNotificationWhatsAppBatchJob` y `SendCorporateEventInvitationEmailJob`.

Comando útil: `php artisan birthday:simulate-dispatch {notification} {--date=}` — simula el envío masivo contra el API con `dry_run=true`, **sin enviar correos reales**.

## 8. Rutas entrantes de la app (web/Filament/Livewire)

Esta app **no tiene API REST propia** (no existe `routes/api.php`). Todas las rutas son de sesión/HTML:

### Públicas / app (`routes/web.php`)

- `GET /` → vista `welcome` (`home`).
- `GET /inscripcion/{token}` → Livewire `App\Livewire\CorporateEvents\PublicRegistration` (`corporate-events.register`), con `throttle:30,1`.
- `GET /dashboard` → dashboard (middleware `auth`, `verified`).
- `GET /up` → health check estándar de Laravel.

### Ajustes de usuario (`routes/settings.php`)

- `settings` → redirect a `settings/profile`.
- `settings/profile` (Livewire, `auth`) — `profile.edit`.
- `settings/appearance` (Livewire, `auth`+`verified`) — `appearance.edit`.
- `settings/security` (Livewire, `auth`+`verified`+`password.confirm`) — `security.edit`.

### Auth Fortify / Passkeys

Login, registro, reseteo de contraseña, verificación de email, 2FA y passkeys usan las rutas estándar que registra Fortify (sesión, no API JSON).

### Panel Filament `/marketing`

- Login del panel: `/marketing/login` (página `App\Filament\Auth\Pages\Login`).
- Páginas: `Dashboard`, `CorporateEventsCalendar`, `EditorialCalendar`.
- CRUD/gestión (slugs reales de cada `Resource`): `notificaciones-cumpleanos`, `notificaciones-masivas`, `eventos-corporativos`, `grupos-clientes`, `cuentas-redes`, `publicaciones`, `roles-marketing`.
- Historial: `historial-envios`.
- Audiencias (solo lectura vía API): `agentes-corretaje`, `agencias-corretaje`, `agentes-viajes`, `agencias-viajes`, `afiliados-individuales`, `afiliados-corporativos`, `proveedores-naturales`, `proveedores-juridicos`, `colaboradores`.
- Grupos de navegación (`MarketingPanelProvider::navigationGroups()`): **Operaciones**, **Audiencias TDG**, **Administración**.

El frontend no consume `/api/*` local; usa Livewire (`/livewire/update` u homólogo) y páginas Filament renderizadas en HTML.

## 9. Modelos (persistidos vs. API-backed)

### Persistidos localmente (con migración en `database/migrations`)

`User`, `MarketingRole`, `BirthdayNotification`, `MassNotification`, `NotificationDispatchLog`, `CorporateEvent`, `CorporateEventRegistration`, `SocialAccount`, `EditorialPublication`, `ClientGroup`, `Client`.

### API-backed (sin tabla, sin migración, sin timestamps)

`BrokerAgent`, `BrokerAgency`, `TravelAgent`, `TravelAgency`, `IndividualAffiliate`, `CorporateAffiliate`, `NaturalSupplier`, `LegalSupplier`, `RrhhColaborador`.

Patrón común (ver `App\Models\TravelAgency` como referencia): `$incrementing = false`, `$keyType = 'string'`, `$timestamps = false`, `$guarded = []`, y un constructor estático `fromApi(array $attributes)` que hace `forceFill()` + `exists = true`. **No crear migraciones ni factories de base de datos para estos modelos.**

## 10. Permisos y roles

`App\Marketing\MarketingPermission` define las constantes de permiso, sus etiquetas/descripciones en español y los agrupa por área (`groups()`) para la UI de gestión de roles. Dominios de permiso: `social_accounts.*`, `publications.*` (incluye `publications.approve`), `calendar.view`, `roles.manage`, `birthday_notifications.*`, `mass_notifications.*`, `corporate_events.*`, `notification_logs.view`, `client_groups.*`.

`MarketingRole` guarda una lista saneada (`MarketingPermission::sanitize()`) de esos permisos. Roles sembrados por `database/seeders/MarketingRoleSeeder.php` (todos `is_system = true`):

| Slug | Nombre | Alcance |
|---|---|---|
| `administrador` | Administrador de marketing | Todos los permisos (`MarketingPermission::all()`) |
| `analista` | Analista de marketing | Redes, publicaciones, calendario, cumpleaños, masivas, eventos, logs, grupos de clientes (sin `roles.manage` ni `publications.approve`) |
| `aprobador` | Aprobador editorial | Ver cuentas/publicaciones/calendario + `publications.approve` |
| `visor` | Visor autorizado | Solo lectura de cuentas, publicaciones y calendario |

`database/seeders/MarketingAdministratorSeeder.php` asigna el rol `administrador` a un usuario usando `env('MARKETING_ADMIN_PASSWORD', 'password')`.

`User` obtiene `hasMarketingPermission()`, `canManageMarketingOperations()` e `isMarketingAdministrator()` (slug `administrador`) vía el concern `App\Models\Concerns\HasMarketingPermissions`. Las Policies en `app/Policies` delegan la verificación a través de `InteractsWithMarketingPermissions`. **No se usa Spatie Permission** ni ningún paquete de roles de terceros.

## 11. Jobs, colas y comando de simulación

Ver el detalle completo de payloads en la sección 7 ("Jobs relacionados con el API"). Resumen de colas: `QUEUE_CONNECTION=database` en local (`sync` en tests).

**Dos colas, no una.** Los jobs de correo (`SendBirthdayEmailBatchJob`, `SendMassNotificationEmailBatchJob`, `SendCorporateEventInvitationEmailJob`) se auto-asignan a la cola `email` con `$this->onQueue('email')` en su constructor; el resto va a `default`. Por eso `composer run dev` levanta `php artisan queue:listen --queue=default,email`. Si añades un job de correo nuevo, respeta esa convención; si levantas el worker a mano, **incluye ambas colas** o los correos se quedarán encolados sin procesar.

### Diagnóstico: "el envío masivo no envió nada"

Síntoma típico: el floater se queda clavado en «Encolando N lote(s) de correo…» (~95%) y el resumen de la campaña muestra *0 enviados / N fallidos*. **Antes de sospechar del API de correos, comprueba el worker** — casi siempre es que no hay ninguno consumiendo la cola `email`:

```sql
SELECT queue, COUNT(*) FROM jobs GROUP BY queue;   -- lotes esperando, attempts = 0
SELECT COUNT(*) FROM failed_jobs;                  -- si es 0, los jobs ni se intentaron
```

Si los lotes siguen en `jobs` con `attempts = 0` y `failed_jobs` está vacío, **los correos no fallaron: nunca se intentaron**. No hay que relanzar la campaña (duplicaría envíos): basta levantar el worker con ambas colas y los lotes pendientes se procesan solos.

Para evitar que vuelva a pasar en silencio, `QueueWorkerHealthInspector` comprueba un latido que cada worker deja en caché (`Queue::looping()` registrado en `AppServiceProvider`); `SendMassNotificationAction` **rechaza el envío y avisa** si ninguna de las colas `default`/`email` tiene worker vivo. Con la conexión `sync` (tests) el chequeo se omite porque los jobs corren en el acto.

### Diagnóstico: «el envío se detuvo a mitad» (cuota del proveedor de correo)

Si el historial muestra los primeros lotes en *Enviado* y el resto con `550 5.4.5 Daily user sending limit exceeded` o `454 4.7.0 Too many login attempts`, **no es un fallo de la app ni de los destinatarios**: la cuenta remitente agotó su cuota diaria (Gmail gratuito ≈ 500 correos/24 h; Workspace ≈ 2.000, en ventana móvil, no por día calendario). El 454 suele ser la secuela del 550: al cortar el proveedor, el pool SMTP se reabre y cada reconexión cuenta como intento de login.

Qué hace la app por sí sola desde entonces (no hay que relanzar la campaña):

- `EmailDispatchFailureClassifier` traduce la respuesta del API a `EmailDispatchFailureKind` (cuota, auth, temporal, permanente). El código SMTP puede venir solo dentro de `failures[]` cuando el envío fue parcial (HTTP 207), por eso se clasifica el texto compuesto.
- `MassEmailCircuitBreaker` (caché, como el latido de `QueueWorkerHealthInspector`) abre un freno global del canal correo ante cuota o bloqueo de auth. Los lotes pendientes se reprograman **sin llamar al API**, en vez de quemarse en cadena.
- `SendMassNotificationEmailBatchJob` no usa `release()`: reencola una **copia con los destinatarios que faltaban** (`reschedule()`), porque `release()` reencola el payload original y volvería a escribir a quienes ya recibieron el correo. Tope de 6 intentos y 24 esperas por freno.
- Los lotes salen escalonados (`mass_email_batch_pause_seconds`), igual que WhatsApp.

**Nunca relances una campaña que quedó a medias**: los lotes pendientes se reanudan solos y un envío nuevo duplica los correos ya entregados. Del lado de `integracorp-api`, `email.service.js` corta el lote en cuanto detecta un bloqueo del remitente y devuelve los pendientes en `failures[]`, que es justo lo que esta app usa para reintentar solo esas direcciones.

### Diagnóstico: `TimeoutExceededException` / `ProcessTimedOutException` en los lotes de correo

Síntoma: el worker muestra `SendMassNotificationEmailBatchJob ... RUNNING` y muere con `ProcessTimedOutException` (proceso hijo de `queue:listen`) o `TimeoutExceededException` en `failed_jobs`, siempre a los ~60 s.

Un lote de correo tarda minutos **por diseño**: son hasta `mass_email_batch_size` (50) destinatarios en un único POST a `/api/emails/bulk`, y el API entrega por SMTP antes de responder — por eso el cliente HTTP espera hasta `MARKETING_API_EMAIL_TIMEOUT` (300 s). Los tres jobs de correo declaran `$timeout = email_timeout + 60` para que el worker les dé ese margen, pero **`queue:listen` ignora el `$timeout` del job**: el proceso padre mata al hijo a los segundos de su propio `--timeout` (60 por defecto). Levanta el worker con `composer run queue` (o `composer run dev`), que pasan `--timeout=0`; un `php artisan queue:listen` a secas reproduce el fallo.

Cuando el job muere por timeout no corre **nada** del manejo de errores: ni la traza de `MarketingApiTraceRecorder`, ni el log de despacho, ni el `MassEmailCircuitBreaker`, ni el `reschedule()` de pendientes. Pero el POST ya llegó al API, que sigue enviando. Por eso **no hagas `queue:retry` de esos lotes**: los correos probablemente salieron y los duplicarías (es también la razón del `$tries = 1`).

Recuerda además `php artisan config:clear` si la config está cacheada (`bootstrap/cache/config.php`): un worker con caché vieja usa los timeouts anteriores.

 El progreso en vivo se muestra globalmente en el panel: `DispatchProgressBroadcaster::started()` dispara un evento Livewire hacia `MarketingDispatchProgressFloaterWidget`, renderizado en cada página autenticada mediante un hook `PanelsRenderHook::BODY_END` registrado en `MarketingPanelProvider::boot()` — el progreso se coordina con `DispatchProgressTracker` (cache), **no con Events/Listeners de Laravel**.

Comando de simulación: `php artisan birthday:simulate-dispatch {notification} {--date=}` — usa `dry_run=true` contra el API, no envía correos reales.

## 12. Convenciones de código y Filament a imponer

1. Español en UI, labels de Filament, mensajes al usuario y en toda comunicación del agente.
2. La lógica de negocio va en `app/Services/Marketing/`, no en controllers ni en Resources "gordos".
3. Estructura de recurso Filament v5: `XResource.php` + `Pages/` + `Schemas/` + `Tables/` + `Support/*Presentation` (y `Actions/` cuando aplique). Sigue el layout de un recurso hermano antes de crear uno nuevo.
4. Enums de dominio en `App\Marketing\`, implementando `HasLabel`/`HasColor`/`HasIcon` cuando la UI lo requiera (ver `PublicationStatus`, `BirthdayNotificationChannel`).
5. Audiencias externas: patrón `fromApi()` + servicio que extiende `MarketingPaginatedApiService`. **No crear migraciones** para ellas.
6. Autorización vía `MarketingPermission` + Policies + `hasMarketingPermission()` — nunca hardcodear checks de rol.
7. Al crear registros de marketing desde el panel, usa el concern `App\Filament\Concerns\SetsMarketingAuthor` (setea `created_by_id` en `mutateFormDataBeforeCreate`) — ya se usa en las páginas `Create*` de `BirthdayNotification`, `SocialAccount`, `EditorialPublication`, `ClientGroup`, `CorporateEvent`, `MassNotification`.
8. El progreso de envíos se coordina vía cache/`DispatchProgressTracker` + el widget floater — **no** uses Events/Listeners de Laravel (no existen en este proyecto).
9. El HTML de los correos se renderiza en esta app (`BirthdayNotificationEmailRenderer`, `MassNotificationEmailRenderer`, `CorporateEventInvitationEmailRenderer` según el flujo) y se envía vía el endpoint bulk del API externo — no basta con `Mail::send()`/la facade `Mail` sola.
10. Tests Pest obligatorios por cada cambio; usa SQLite `:memory:` y, si el test toca la BD, adhiérete con `uses(SafeRefreshDatabase::class);` (ver sección 14).
11. Antes de tocar un dominio, activa la skill correspondiente (`.claude/skills/`, ver sección 15) y usa `search-docs` de Boost antes de escribir código.
12. Toda llamada nueva al Marketing API desde un job/servicio debe registrar su traza con `MarketingApiTraceRecorder` y persistirla en el `NotificationDispatchLog` (ver sección 7).
13. Tras editar PHP: `vendor/bin/pint --dirty --format agent`.
14. No cambies dependencias del proyecto sin aprobación explícita.
15. No crees archivos de documentación Markdown salvo que el usuario lo pida explícitamente (esta actualización de `CLAUDE.md` sí fue solicitada).

## 13. Variables de entorno críticas

Ver el listado completo en la sección 7. Además de las variables del Marketing API, ten en cuenta: `QUEUE_CONNECTION` (colas), `DB_CONNECTION`/`DB_DATABASE` (BD local, SQLite por defecto), `MAIL_MAILER` (solo relevante para correos que **no** pasan por el bulk del API externo), y `APP_URL=https://tdg-marketing.test`.

## 14. Testing

- Framework: **Pest v4**. Crea tests con `php artisan make:test --pest {Nombre}` (sin incluir el directorio de suite: `SomeFeatureTest`, no `Feature/SomeFeatureTest`).
- `tests/Concerns/SafeRefreshDatabase.php` envuelve `RefreshDatabase` y **lanza una excepción** si la conexión activa no es SQLite `:memory:` — protege contra truncar una base MySQL real de desarrollo. Se adhiere **por archivo** con `uses(SafeRefreshDatabase::class);` (no está aplicado globalmente en `tests/Pest.php`). Sigue el mismo patrón en cualquier test nuevo que toque la BD.
- `.env.testing` fuerza SQLite `:memory:`, caché/sesión `array`, cola `sync`, mail `array`. Además `phpunit.xml` fija `DB_CONNECTION`/`DB_DATABASE` con `force="true"` y vacía `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD`/`DB_URL` — esa es la segunda barrera que impide que un test toque una BD real. No relajes esos `force`.
- Para modelos API-backed y llamadas al Marketing API externo, **mockea con `Http::fake(['http://localhost:4000/api/...' => Http::response(...)])`** en vez de sembrar datos en BD (ver `tests/Feature/TravelAgencyResourceTest.php` como referencia del patrón).
- Comandos: `php artisan test --compact` (toda la suite) o con `--filter=nombreDelTest` / ruta de archivo para un subconjunto.
- Antes de dar por cerrado un cambio, `composer run test` replica el chequeo de CI (limpia config + `lint:check` + suite completa). CI corre `./vendor/bin/pest` en PHP 8.3/8.4/8.5.

## 15. Skills a activar

Activa la skill correspondiente en cuanto el trabajo entre en su dominio — no esperes a quedar bloqueado:

- `laravel-best-practices`
- `pest-testing`
- `livewire-development`
- `fluxui-development`
- `fortify-development`
- `tailwindcss-development`

## 16. Anti-patrones — qué NO hacer

- **No** reintroducir "campaigns" ni "segments" de marketing sin diseño explícito del usuario (fueron eliminados deliberadamente, ver sección 3).
- **No** inventar ni crear `routes/api.php` — esta app no expone API REST propia.
- **No** persistir localmente (migración/tabla) datos de las audiencias que vienen del Marketing API externo (agentes, agencias, afiliados, proveedores, colaboradores RRHH) — deben seguir siendo `fromApi()`.
- **No** introducir `app/Events`/`app/Listeners` para el progreso de envíos u otros flujos — el patrón existente usa cache/tracker + Livewire.
- **No** usar Spatie Permission ni otro paquete de roles — el RBAC es propio (`MarketingRole` + `MarketingPermission`).
- **No** llamar a `MARKETING_API_MASS_SEND_PATH` (`/api/notifications/mass/send`) — está configurado pero no se usa; el flujo real de WhatsApp masivo pasa por `mass_send_batch_path`.
- **No** enviar correos de cumpleaños/masivos solo con la facade `Mail` — el envío real pasa por el endpoint bulk del Marketing API externo.
- **No** ejecutar `RefreshDatabase` fuera de SQLite `:memory:` (bloqueado por `SafeRefreshDatabase`, no lo bypasees).

## 17. Proyecto hermano: `integracorp-api` (para tareas que toquen ambos repos)

Ubicación local: `~/integracorp-api` — repositorio **separado**, no es una carpeta de este proyecto. Es la API Node/Express que implementa todo lo descrito en la sección 7 ("Marketing API externo"). Cuando una tarea implique una ruta nueva, un campo nuevo o depurar una integración, es muy probable que haya que tocar código en ambos repos a la vez.

**Fuente de verdad de ese repo:** su propio `~/integracorp-api/CLAUDE.md`. Léelo antes de modificar código allí — este apartado es solo un resumen orientado a la integración entre ambos proyectos, no lo sustituye.

### Identidad y stack de `integracorp-api`

- Node.js ≥ 18, Express 4, MySQL (`mysql2/promise`), puerto por defecto `4000`.
- Código, comentarios y documentación en **español** — misma convención que este repo.
- Arranque local: `npm run dev` (nodemon, single-process) o `npm run dev:cluster`. Para probar envíos masivos de WhatsApp, usa single-process o el worker marcado con `WHATSAPP_WORKER=1` — en cluster, los demás workers responden `503` a los lotes.
- Docs vivas (más confiables que memorizar rutas): `http://localhost:4000/docs/endpoints.html`, `http://localhost:4000/docs/openapi.json`, `GET /api/endpoints` (con API Key). Fuente de verdad interna: `src/config/endpointsCatalog.js`.

### Contrato de autenticación entre ambos repos

- `App\Services\Marketing\MarketingApiHttpFactory` (este repo) manda `X-API-Key: <MARKETING_API_KEY>` en cada request saliente.
- `integracorp-api` valida esa cabecera contra su propia `INTEGRACORP_API_KEY` en `src/middleware/apiKey.js` (comparación timing-safe).
- **Para que ambos repos se comuniquen en local, `MARKETING_API_KEY` (aquí) y `INTEGRACORP_API_KEY` (allá) deben tener el mismo valor.**
- Si `INTEGRACORP_API_KEY` no está configurada en el servidor Node, éste responde `401` con mensaje explícito — pero del lado Laravel, `MarketingPaginatedApiService` **traga el error en silencio** y devuelve un paginador vacío. Si una tabla de audiencias en Filament aparece vacía sin errores visibles, revisa primero que la API Key coincida en ambos `.env` antes de sospechar de la query o del modelo.
- `GET /api/health` y `GET /api/health/db` son las únicas rutas relevantes que **no** requieren API Key — útiles para descartar "el servidor Node está caído" antes de revisar autenticación.

### Contrato de endpoints ya usados desde este repo (validado contra ambos `CLAUDE.md`)

Las rutas listadas en la sección 7 coinciden 1:1 con lo que expone `integracorp-api`:

| Uso en Laravel | Ruta | Auth en `integracorp-api` | Nota |
|---|---|---|---|
| Widgets de salud | `/api/health`, `/api/health/db` | público | sin API Key |
| Audiencias (9 recursos, ver sección 7) | `/api/agencies`, `/api/agents`, `/api/travel-agencies`, `/api/travel-agents`, `/api/affiliates`, `/api/affiliate-corporates`, `/api/suppliers`, `/api/doctor-nurses`, `/api/rrhh-colaboradores` | API Key | ver nota de paginación abajo |
| Correo masivo | `POST /api/emails/bulk` | API Key | `multipart/form-data` del lado API; ver nota de adjuntos |
| WhatsApp por lotes | `POST /api/notifications/mass/send-batch` | API Key | máx. 50 destinatarios, responde `200`/`202`, delay anti-ban |
| WhatsApp/SMS de prueba | `POST /api/notifications/birthday/test` | API Key | el canal `sms` responde `501` (no implementado del lado API) aunque `BirthdayNotificationChannel::Sms` ya existe en Laravel |

Endpoints que **existen en `integracorp-api` pero no se llaman desde este repo** — candidatos naturales si se pide una integración nueva:
- `POST /api/notifications/mass/send` — envío unitario inmediato. Laravel siempre usa el batch; la env `MARKETING_API_MASS_SEND_PATH` está configurada pero sin uso (ver secciones 7 y 16).
- `GET /api/notifications/whatsapp/status` — estado de Ultramsg y de la cola. Hoy solo aparece como texto de ayuda para el analista en `NotificationDispatchFailureResolver`, nunca se invoca por HTTP.

### Paginación — detalle importante para depurar

`integracorp-api` documenta **`PAGE_SIZE` fijo = 10, no configurable por query**, para *todos* los recursos paginados (no solo RRHH). El `MarketingPaginatedApiService` genérico de Laravel igual manda `limit` en la query (por defecto 10; `BirthdayNotificationRecipientCollector` pide `perPage: 100`). Si el API ignora ese parámetro y siempre devuelve páginas de 10, Laravel simplemente da más vueltas en su `do/while` de paginación — no es un bug funcional, pero explica por qué recolectar cumpleaños de una audiencia grande genera más requests de las que parece pedir el código. Solo `MarketingRrhhColaboradoresApiService` compensa esto explícitamente agregando todo en memoria; los demás servicios de audiencia no lo hacen.

### Adjuntos en correo masivo

El body real que envían `BirthdayNotificationBulkEmailService`/`BirthdayNotificationTestSendService` (`recipients` como JSON-string, `copy`, `subject`, `dry_run` opcional, más adjuntos `logo` e `image` vía `App\Mail\BirthdayNotificationEmailAttachments::attach()`) coincide con lo que documenta `integracorp-api` para `POST /api/emails/bulk` (`multipart/form-data`, campos `recipients`, `copy`, `subject`, `image`, `logo`, `dry_run`, requiere al menos `copy` o `image`).

### Cómo depurar/verificar sin adivinar

- Antes de asumir el shape de una respuesta nueva, revisa el controller correspondiente en `~/integracorp-api/src/controllers/` en vez de inferirlo solo desde el consumidor Laravel.
- Códigos de error relevantes del lado API: `401` (API Key ausente/incorrecta), `404`, `413` (upload muy grande), `429` (rate limit), `502` (falla Ultramsg), `503` (worker sin cola WhatsApp o Ultramsg sin configurar). `NotificationDispatchFailureResolver` (Laravel) ya tiene mensajes de remediación para varios de estos casos.

### Reglas al hacer una tarea que toca ambos repos

1. Si el cambio es de **contrato** (ruta nueva, campo nuevo, status code nuevo), hay que tocar `integracorp-api` (controller + ruta + las capas de documentación que exige su propio checklist: catálogo, openapi, HTML) **y** el servicio Laravel correspondiente en `app/Services/Marketing/`.
2. Si el cambio es solo de consumo de un endpoint que ya existe en `integracorp-api` pero no está mapeado aún en Laravel, no toques `integracorp-api` — solo el lado Laravel.
3. Mantén el idioma español en ambos repos (código, comentarios, docs, respuestas al agente) — es una regla compartida por los dos `CLAUDE.md`.
4. No dupliques lógica de negocio: el envío real, el cálculo de KPIs, el formateo de teléfonos para Ultramsg, la generación de PDFs, etc. viven en `integracorp-api`; este repo solo orquesta, valida localmente y muestra la UI.
5. Para correr ambos en local: `integracorp-api` en `http://localhost:4000` (`npm run dev`) y `tdg-marketing` vía Herd en `https://tdg-marketing.test`, con `MARKETING_API_KEY` (aquí) igual a `INTEGRACORP_API_KEY` (allá).

---

# BLOQUE B — Guidelines técnicas (Laravel Boost / stack)

## 18. Contexto técnico base

- PHP 8.4
- `filament/filament` (FILAMENT) v5
- `laravel/fortify` (FORTIFY) v1
- `laravel/framework` (LARAVEL) v13
- `laravel/prompts` (PROMPTS) v0
- `livewire/flux` (FLUXUI_FREE) v2
- `livewire/livewire` (LIVEWIRE) v4
- `laravel/boost` (BOOST) v2
- `laravel/chisel` v0 (toolkit de limpieza de código; sin uso propio en el repo)
- `laravel/pao` v1 (salida de tests optimizada para agentes)
- `laravel/tinker` v3
- `laravel/mcp` (MCP) v0
- `laravel/pail` (PAIL) v1
- `laravel/pint` (PINT) v1
- `laravel/sail` (SAIL) v1
- `pestphp/pest` (PEST) v4
- `phpunit/phpunit` (PHPUNIT) v12
- `tailwindcss` (TAILWINDCSS) v4

### Activación de skills

Este proyecto tiene skills específicas por dominio en `**/skills/**`. Actívalas apenas el trabajo entre en su dominio — no esperes a quedar bloqueado.

### Convenciones generales

- Sigue las convenciones ya existentes en la app. Antes de crear o editar un archivo, revisa archivos hermanos para copiar estructura, enfoque y nomenclatura.
- Usa nombres descriptivos para variables y métodos (`isRegisteredForDiscounts`, no `discount()`).
- Revisa si ya existe un componente reutilizable antes de escribir uno nuevo.
- No crees scripts de verificación ni uses `tinker` cuando los tests ya cubren esa funcionalidad y prueban que funciona. Los tests unitarios y de feature son más importantes.
- Respeta la estructura de directorios existente; no crees carpetas base nuevas sin aprobación.
- No cambies las dependencias de la aplicación sin aprobación.
- Si el usuario no ve reflejado un cambio de frontend, puede que falte `npm run build`, `npm run dev` o `composer run dev` — pregúntale.
- Solo crea archivos de documentación si el usuario lo pide explícitamente.
- Sé conciso en las explicaciones: enfócate en lo importante, no en detalles obvios.

### Herramientas de Laravel Boost (MCP)

- Boost es un servidor MCP diseñado para esta app. Prefiere sus herramientas sobre alternativas manuales (comandos de shell, lectura manual de archivos).
- `database-query` para consultas de solo lectura en vez de escribir SQL crudo en tinker.
- `database-schema` para inspeccionar tablas antes de escribir migraciones o modelos.
- `get-absolute-url` para resolver esquema/dominio/puerto correctos antes de compartir una URL con el usuario.
- `browser-logs` para leer logs/errores/excepciones del navegador (solo entradas recientes son útiles).

#### Búsqueda de documentación (`search-docs`) — IMPORTANTE

- Usa siempre `search-docs` antes de hacer cambios de código. No te lo saltes: devuelve documentación específica de las versiones instaladas.
- Pasa un array `packages` para acotar resultados cuando sepas qué paquetes son relevantes.
- Usa varias queries amplias por tema: `['rate limiting', 'routing rate limiting', 'routing']`.
- No agregues el nombre del paquete a la query (ya se comparte el contexto de paquetes). Usa `test resource table`, no `filament 5 test resource table`.
- Sintaxis: palabras sueltas = AND con stemming (`rate limit` matchea "rate" Y "limit"); `"frase exacta"` = coincidencia de posición exacta; combina ambas (`middleware "rate limit"`); varias queries = lógica OR.

### Artisan y Tinker

- Ejecuta comandos Artisan directamente (`php artisan route:list`). Usa `php artisan list` para descubrir comandos y `php artisan [comando] --help` para ver parámetros.
- Inspecciona rutas con `php artisan route:list`, filtrando con `--method=GET`, `--name=`, `--path=`, `--except-vendor`, `--only-vendor`.
- Lee configuración con notación de puntos: `php artisan config:show app.name`, o directamente en `config/`.
- En tinker, no crees modelos sin aprobación del usuario; prefiere tests con factories. Prefiere comandos Artisan existentes sobre código tinker a medida.
- Usa siempre comillas simples para evitar expansión del shell: `php artisan tinker --execute 'Model::count();'` (comillas dobles solo para strings PHP dentro: `'User::where("active", true)->count();'`).

### Reglas de PHP

- Usa siempre llaves en estructuras de control, incluso para cuerpos de una sola línea.
- Usa property promotion del constructor (PHP 8): `public function __construct(public GitHub $github) {}`. No dejes `__construct()` vacíos sin parámetros salvo que sea privado.
- Declara tipos de retorno y de parámetros explícitos en todos los métodos.
- Usa `TitleCase` para las keys de Enums (`FavoritePerson`, `Monthly`).
- Prefiere bloques PHPDoc sobre comentarios inline. Solo agrega comentarios inline para lógica excepcionalmente compleja.
- Usa "array shapes" en los bloques PHPDoc cuando aplique.

### Despliegue

- Laravel Cloud (`https://cloud.laravel.com/`) es la vía más rápida para desplegar y escalar esta app en producción.

### Laravel Herd

- La app corre en Laravel Herd en `https://tdg-marketing.test`. Usa `get-absolute-url` para generar URLs válidas. **Nunca** ejecutes comandos para "levantar" el sitio — siempre está disponible.
- Usa el CLI `herd` para gestionar servicios, versiones de PHP y sitios (`herd sites`, `herd services:start <servicio>`, `herd php:list`). `herd list` para descubrir comandos.

### Exigencia de tests

- Todo cambio debe probarse de forma programática: escribe o actualiza un test y corre los tests afectados para confirmar que pasan.
- Corre el mínimo de tests necesario para garantizar calidad y velocidad: `php artisan test --compact` con un archivo o `--filter` específico.
- No borres tests sin aprobación.

### El estilo "Laravel"

- Usa comandos `php artisan make:` para crear archivos nuevos (migraciones, controladores, modelos, etc.). Para una clase PHP genérica, usa `php artisan make:class`.
- Pasa `--no-interaction` a todos los comandos Artisan para que funcionen sin input del usuario, junto con las `--opciones` correctas.
- Al crear modelos nuevos, crea también factories y seeders útiles; pregunta al usuario si necesita algo más (revisa `php artisan make:model --help`).
- Para APIs, usa por defecto Eloquent API Resources y versionado de API, salvo que las rutas existentes ya sigan otra convención (en esta app, recuerda: **no hay API propia**).
- Al generar enlaces a otras páginas, prefiere rutas con nombre y el helper `route()`.
- Al crear modelos para tests, usa las factories correspondientes; revisa si la factory ya tiene "states" reutilizables antes de armar el modelo a mano. Usa `$this->faker->word()` o `fake()->randomDigit()` siguiendo la convención ya usada en el archivo.
- Para crear tests: `php artisan make:test [opciones] {nombre}`, con `--unit` para tests unitarios. La mayoría deben ser tests de feature (Pest).

### Livewire

- Livewire permite construir interfaces dinámicas y reactivas en PHP sin escribir JavaScript.
- Puedes usar Alpine.js para interacciones del lado del cliente en vez de frameworks JS completos.
- Mantén el estado del lado del servidor para que la UI lo refleje. Valida y autoriza en las acciones igual que harías en un request HTTP.

### Comandos habituales del proyecto

- `composer run setup` — puesta a punto inicial de un clon: `composer install`, `.env`, `key:generate`, `storage:link`, `migrate`, `npm install`, `npm run build`.
- `composer run dev` — arranca en local: servidor PHP, `queue:listen --queue=default,email`, `pail` (logs) y Vite, en paralelo. Con Herd el sitio ya responde en `https://tdg-marketing.test`; lo que realmente necesitas de aquí es el **worker de colas** y Vite.
- `npm run dev` / `npm run build` — solo Vite.
- `composer run lint` — corrige estilo (`pint --parallel`). `composer run lint:check` — solo verifica, sin corregir.
- `php artisan test --compact` — corre toda la suite. Añade `--filter=nombreDelTest` o una ruta para correr un subconjunto.
- `composer run test` (alias `composer run ci:check`) — limpia config, corre `lint:check` y luego toda la suite (equivalente al chequeo de CI).
- `php artisan boost:mcp` — servidor MCP de Laravel Boost (ya conectado vía `.mcp.json`).

### Pint (formateo)

- Si modificaste archivos PHP, corre `vendor/bin/pint --dirty --format agent` antes de dar por terminado el cambio, para ajustarte al estilo del proyecto.
- No uses `vendor/bin/pint --test --format agent`; simplemente corre `vendor/bin/pint --format agent` para corregir el formato.

### Pest

- El proyecto usa Pest para testing. Crea tests con `php artisan make:test --pest {nombre}` (el `{nombre}` no debe incluir el directorio de la suite: usa `SomeFeatureTest`, no `Feature/SomeFeatureTest`).
- Corre tests con `php artisan test --compact` o filtrando con `--filter=nombreDelTest`.
- No borres tests sin aprobación.
