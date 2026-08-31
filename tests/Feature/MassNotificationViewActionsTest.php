<?php

use App\Filament\Resources\MassNotifications\Pages\CreateMassNotification;
use App\Filament\Resources\MassNotifications\Pages\ViewMassNotification;
use App\Jobs\SendMassNotificationEmailBatchJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\MassNotificationDeliverySummary;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\MassNotificationTestSendService;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function massViewActionsUser(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

function fakeCollaboratorsApi(): void
{
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'data' => [
                [
                    'id' => 12,
                    'fullName' => 'Ana Aular',
                    'emailCorporativo' => 'aaular@tudrencasa.com',
                    'telefonoMovil' => '04121234567',
                ],
                [
                    'id' => 20,
                    'fullName' => 'Gustavo Camacho',
                    'emailCorporativo' => 'gcamacho@tudrencasa.com',
                    'telefonoMovil' => '04129876543',
                ],
                [
                    'id' => 22,
                    'fullName' => 'Marketing TDG',
                    'emailCorporativo' => 'marketing@tudrencasa.com',
                    'telefonoMovil' => '04121112233',
                ],
            ],
            'pagination' => [
                'page' => 1,
                'totalPages' => 1,
                'total' => 3,
            ],
        ], 200),
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 3,
            'total' => 3,
        ], 200),
        '*/api/notifications/birthday/test' => Http::response([
            'success' => true,
            'message' => 'ok',
        ], 200),
    ]);
}

test('dispatch existing sends email to configured recipient ids', function () {
    fakeCollaboratorsApi();

    $user = massViewActionsUser([MarketingPermission::ManageMassNotifications]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'prueba 19',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'recipient_ids' => ['20', '12', '22'],
        'created_by_id' => $user->getKey(),
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatchExisting($notification, $user);

    expect($result->queued)->toBeTrue()
        ->and($result->dispatchRunId)->not->toBeNull()
        ->and($result->allSuccessful())->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && str_contains($body, 'aaular@tudrencasa.com')
            && str_contains($body, 'gcamacho@tudrencasa.com')
            && str_contains($body, 'marketing@tudrencasa.com');
    });

    app()->terminate();

    $log = NotificationDispatchLog::query()
        ->where('mass_notification_id', $notification->getKey())
        ->where('source', NotificationDispatchSource::MassIndividual->value)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->sent_count)->toBe(3)
        ->and($log->total_count)->toBe(3);
});

test('test send service delivers email for mass notification channels', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = massViewActionsUser([MarketingPermission::ManageMassNotifications]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'Mensaje de prueba',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->getKey(),
    ]);

    $result = app(MassNotificationTestSendService::class)->send(
        notification: $notification,
        channels: [BirthdayNotificationChannel::Email->value],
        email: 'analista@tudoctorgroup.com',
        phone: null,
        sentBy: $user,
    );

    expect($result->allSuccessful())->toBeTrue();

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && str_contains($body, 'analista@tudoctorgroup.com')
            && str_contains($body, 'prueba 19')
            && ! str_contains($body, '[Prueba]');
    });

    $log = NotificationDispatchLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->source)->toBe(NotificationDispatchSource::MassTest->value)
        ->and($log->mass_notification_id)->toBe($notification->getKey());
});

test('delivery summary aggregates mass and test logs', function () {
    $user = massViewActionsUser([MarketingPermission::ViewMassNotifications]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'copy',
        'created_by_id' => $user->getKey(),
    ]);

    NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassIndividual->value,
        'status' => NotificationDispatchStatus::Failed->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => 'prueba 19',
        'summary' => 'read ECONNRESET',
        'sent_count' => 0,
        'total_count' => 3,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
    ]);

    NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassTest->value,
        'status' => NotificationDispatchStatus::Sent->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => '[Prueba] prueba 19',
        'summary' => 'Correo de prueba enviado',
        'sent_count' => 1,
        'total_count' => 1,
        'recipient' => 'analista@tudoctorgroup.com',
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
    ]);

    $summary = app(MassNotificationDeliverySummary::class)->summarize($notification);

    expect($summary['has_activity'])->toBeTrue()
        ->and($summary['sent'])->toBe(0)
        ->and($summary['total'])->toBe(3)
        ->and($summary['failed'])->toBe(3)
        ->and($summary['mass_logs'])->toHaveCount(1)
        ->and($summary['test_logs'])->toHaveCount(1)
        ->and($summary['active_run'])->toBeNull();
});

test('delivery summary keeps mass and test sections independent when many tests exist', function () {
    $user = massViewActionsUser([MarketingPermission::ViewMassNotifications]);
    $notification = MassNotification::factory()->create([
        'title' => 'Campaña mixta',
        'copy' => 'copy',
        'created_by_id' => $user->getKey(),
    ]);

    NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassAudience->value,
        'status' => NotificationDispatchStatus::Partial->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => 'Campaña mixta',
        'summary' => 'Envío parcial',
        'analyst_message' => 'Solo una parte de los destinatarios recibió el mensaje.',
        'sent_count' => 8,
        'total_count' => 10,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'logged_at' => now()->subMinute(),
    ]);

    NotificationDispatchLog::factory()->count(25)->create([
        'source' => NotificationDispatchSource::MassTest->value,
        'status' => NotificationDispatchStatus::Sent->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => '[Prueba] Campaña mixta',
        'summary' => 'Correo de prueba enviado',
        'sent_count' => 1,
        'total_count' => 1,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'logged_at' => now(),
    ]);

    $summary = app(MassNotificationDeliverySummary::class)->summarize($notification);

    expect($summary['mass_logs'])->toHaveCount(1)
        ->and($summary['test_logs'])->toHaveCount(20)
        ->and($summary['sent'])->toBe(8)
        ->and($summary['total'])->toBe(10)
        ->and($summary['failed'])->toBe(2);
});

test('view page shows send actions and delivery summary', function () {
    fakeCollaboratorsApi();

    $user = massViewActionsUser([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'prueba 19',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'recipient_ids' => ['20', '12', '22'],
        'created_by_id' => $user->getKey(),
    ]);

    NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassIndividual->value,
        'status' => NotificationDispatchStatus::Failed->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => 'prueba 19',
        'summary' => 'read ECONNRESET',
        'analyst_message' => 'Gmail/SMTP cortó la conexión durante el envío.',
        'sent_count' => 0,
        'total_count' => 3,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewMassNotification::class, ['record' => $notification->getKey()])
        ->assertOk()
        ->assertSee('Resultado del envío')
        ->assertSee('Resumen del envío masivo')
        ->assertSee('Gmail/SMTP cortó la conexión durante el envío.')
        ->assertActionVisible('sendMassNotification')
        ->assertActionVisible('sendTestMassNotification')
        ->callAction('sendMassNotification')
        ->assertNotified();

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/emails/bulk'));
});

test('send action is disabled and blocks duplicate dispatch while a previous run is still active', function () {
    fakeCollaboratorsApi();
    Queue::fake([SendMassNotificationEmailBatchJob::class]);

    $user = massViewActionsUser([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);
    $notification = MassNotification::factory()->create([
        'title' => 'Campaña con lote pendiente',
        'copy' => 'prueba 19',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'recipient_ids' => ['20', '12', '22'],
        'created_by_id' => $user->getKey(),
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewMassNotification::class, ['record' => $notification->getKey()])
        ->assertActionEnabled('sendMassNotification')
        ->callAction('sendMassNotification')
        ->assertActionDisabled('sendMassNotification')
        // Filament ignora silenciosamente el intento de montar una acción deshabilitada,
        // por lo que un segundo click no vuelve a ejecutar el closure de envío.
        ->callAction('sendMassNotification');

    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, 1);
});

test('create mass notification redirects to view for sending', function () {
    $user = massViewActionsUser([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(CreateMassNotification::class)
        ->fillForm([
            'title' => 'Campaña post-create',
            'copy' => 'Mensaje listo para enviar.',
            'channels' => [BirthdayNotificationChannel::Email->value],
            'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $notification = MassNotification::query()->first();

    expect($notification)->not->toBeNull();
});

test('delivery summary reports queued batches as pending instead of failed', function () {
    $user = massViewActionsUser([MarketingPermission::ViewMassNotifications]);
    $notification = MassNotification::factory()->create([
        'title' => 'Campaña encolada',
        'copy' => 'copy',
        'created_by_id' => $user->getKey(),
    ]);

    // Un envío recién encolado: 9 lotes esperando al worker, sin ningún resultado todavía.
    NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassAudience->value,
        'status' => NotificationDispatchStatus::Queued->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => 'Campaña encolada',
        'summary' => 'Se encolaron 9 lote(s) de correo para 439 destinatarios.',
        'sent_count' => 0,
        'total_count' => 439,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'logged_at' => now(),
    ]);

    $summary = app(MassNotificationDeliverySummary::class)->summarize($notification);

    expect($summary['failed'])->toBe(0)
        ->and($summary['pending'])->toBe(439)
        ->and($summary['sent'])->toBe(0)
        ->and($summary['total'])->toBe(0);
});

test('delivery summary counts processed batches without double counting the queued log', function () {
    $user = massViewActionsUser([MarketingPermission::ViewMassNotifications]);
    $notification = MassNotification::factory()->create([
        'title' => 'Campaña procesada',
        'copy' => 'copy',
        'created_by_id' => $user->getKey(),
    ]);

    NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassAudience->value,
        'status' => NotificationDispatchStatus::Queued->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => 'Campaña procesada',
        'summary' => 'Se encolaron 2 lote(s) de correo para 100 destinatarios.',
        'sent_count' => 0,
        'total_count' => 100,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'logged_at' => now()->subMinutes(2),
    ]);

    NotificationDispatchLog::factory()->count(2)->sequence(
        ['batch_number' => 1],
        ['batch_number' => 2],
    )->create([
        'source' => NotificationDispatchSource::MassAudience->value,
        'status' => NotificationDispatchStatus::Sent->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => 'Campaña procesada',
        'summary' => 'El servidor SMTP aceptó el envío.',
        'sent_count' => 50,
        'total_count' => 50,
        'total_batches' => 2,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'logged_at' => now(),
    ]);

    $summary = app(MassNotificationDeliverySummary::class)->summarize($notification);

    expect($summary['sent'])->toBe(100)
        ->and($summary['total'])->toBe(100)
        ->and($summary['failed'])->toBe(0)
        ->and($summary['pending'])->toBe(0)
        ->and($summary['ratio'])->toBe(100.0);
});

test('send action refuses to queue a dispatch when no queue worker is running', function () {
    fakeCollaboratorsApi();
    Queue::fake([SendMassNotificationEmailBatchJob::class]);

    // Sin latido de worker y con una conexión asíncrona, encolar dejaría el envío muerto.
    config()->set('queue.default', 'database');

    $user = massViewActionsUser([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);

    $notification = MassNotification::factory()->create([
        'title' => 'Campaña sin worker',
        'copy' => 'Mensaje listo para enviar.',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'recipient_ids' => ['20', '12', '22'],
        'created_by_id' => $user->getKey(),
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewMassNotification::class, ['record' => $notification->getKey()])
        ->callAction('sendMassNotification')
        ->assertNotified();

    Queue::assertNothingPushed();

    expect($notification->dispatchLogs()->count())->toBe(0);
});
