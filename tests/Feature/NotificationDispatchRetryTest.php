<?php

use App\Filament\Resources\NotificationDispatchLogs\Pages\ViewNotificationDispatchLog;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\NotificationDispatchFailureInspector;
use App\Services\Marketing\NotificationDispatchRetryService;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function dispatchRetryUser(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

function failedEmailDispatchLog(User $user, MassNotification $notification): NotificationDispatchLog
{
    return NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassIndividual->value,
        'status' => NotificationDispatchStatus::Failed->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => $notification->title,
        'summary' => 'read ECONNRESET',
        'failure_code' => 'smtp_connection_reset',
        'analyst_message' => 'Gmail/SMTP cortó la conexión durante el envío.',
        'resolution_steps' => "1. Reintenta el envío.\n2. Verifica la contraseña de aplicación.",
        'sent_count' => 0,
        'total_count' => 3,
        'recipient' => null,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'technical_detail' => [
            'api_calls' => [
                [
                    'service' => 'marketing_api',
                    'label' => 'Correo masivo (notificación)',
                    'method' => 'POST',
                    'endpoint' => 'http://localhost:4000/api/emails/bulk',
                    'request' => [
                        'recipients' => [
                            'aaular@tudrencasa.com',
                            'gcamacho@tudrencasa.com',
                            'marketing@tudrencasa.com',
                        ],
                        'subject' => $notification->title,
                    ],
                    'response' => [
                        'status' => 502,
                        'successful' => false,
                        'body' => [
                            'success' => false,
                            'error' => 'No se pudo enviar ningún correo',
                            'reason' => 'read ECONNRESET',
                            'failures' => [
                                ['email' => 'aaular@tudrencasa.com', 'reason' => 'read ECONNRESET'],
                                ['email' => 'gcamacho@tudrencasa.com', 'reason' => 'read ECONNRESET'],
                                ['email' => 'marketing@tudrencasa.com', 'reason' => 'read ECONNRESET'],
                            ],
                        ],
                    ],
                    'error' => null,
                ],
            ],
        ],
    ]);
}

function timeoutEmailDispatchLog(User $user, MassNotification $notification): NotificationDispatchLog
{
    return NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassIndividual->value,
        'status' => NotificationDispatchStatus::Failed->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => $notification->title,
        'summary' => 'El API de correos no confirmó el resultado a tiempo. Es probable que los correos sí se hayan enviado.',
        'failure_code' => 'api_email_timeout_unconfirmed',
        'analyst_message' => 'El API de correos tardó más de lo esperado en responder, pero es probable que los correos sí se hayan enviado.',
        'resolution_steps' => "1. Verifica manualmente si los destinatarios recibieron el correo.\n2. No reintentes si ya se enviaron.",
        'sent_count' => 0,
        'total_count' => 2,
        'recipient' => null,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'technical_detail' => [
            'api_calls' => [
                [
                    'service' => 'marketing_api',
                    'label' => 'Correo masivo (notificación)',
                    'method' => 'POST',
                    'endpoint' => 'http://localhost:4000/api/emails/bulk',
                    'request' => [
                        'recipients' => ['aaular@tudrencasa.com', 'gcamacho@tudrencasa.com'],
                        'subject' => $notification->title,
                    ],
                    'response' => null,
                    'error' => [
                        'type' => 'connection',
                        'message' => 'cURL error 28: Operation timed out after 120002 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://localhost:4000/api/emails/bulk',
                    ],
                ],
            ],
        ],
    ]);
}

test('failure inspector blocks retry when the original failure was an unconfirmed timeout', function () {
    $user = dispatchRetryUser([
        MarketingPermission::ViewNotificationLogs,
        MarketingPermission::ManageMassNotifications,
    ]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba timeout',
        'copy' => 'Mensaje de prueba',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->getKey(),
    ]);
    $log = timeoutEmailDispatchLog($user, $notification);

    $inspection = app(NotificationDispatchFailureInspector::class)->inspect($log);

    expect($inspection['can_retry'])->toBeFalse()
        ->and($inspection['retry_block_reason'])->toContain('evitar duplicados');

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewNotificationDispatchLog::class, ['record' => $log->getKey()])
        ->assertOk()
        ->assertActionHidden('retryDispatch');
});

test('failure inspector extracts per-recipient failures and progress', function () {
    $user = dispatchRetryUser([MarketingPermission::ViewNotificationLogs]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'Mensaje de prueba',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->getKey(),
    ]);
    $log = failedEmailDispatchLog($user, $notification);

    $inspection = app(NotificationDispatchFailureInspector::class)->inspect($log);

    expect($inspection['sent'])->toBe(0)
        ->and($inspection['total'])->toBe(3)
        ->and($inspection['failed'])->toBe(3)
        ->and($inspection['ratio'])->toBe(0.0)
        ->and($inspection['can_retry'])->toBeTrue()
        ->and($inspection['failures'])->toHaveCount(3)
        ->and($inspection['failures'][0]['address'])->toBe('aaular@tudrencasa.com')
        ->and($inspection['failures'][0]['reason'])->toBe('read ECONNRESET')
        ->and($inspection['failures'][0]['analyst_message'])->toContain('Gmail/SMTP');
});

test('failure inspector explains gmail no such user bounce per recipient', function () {
    $user = dispatchRetryUser([MarketingPermission::ViewNotificationLogs]);
    $notification = MassNotification::factory()->create([
        'title' => 'Seminario',
        'copy' => 'Mensaje',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->getKey(),
    ]);

    $log = NotificationDispatchLog::factory()->create([
        'source' => NotificationDispatchSource::MassAudience->value,
        'status' => NotificationDispatchStatus::Partial->value,
        'channel' => BirthdayNotificationChannel::Email->value,
        'title' => $notification->title,
        'summary' => 'Envío parcial: 1 de 2 correos entregados.',
        'sent_count' => 1,
        'total_count' => 2,
        'mass_notification_id' => $notification->getKey(),
        'sent_by_id' => $user->getKey(),
        'technical_detail' => [
            'api_calls' => [
                [
                    'response' => [
                        'successful' => true,
                        'body' => [
                            'success' => true,
                            'sent' => 1,
                            'total' => 2,
                            'failures' => [
                                [
                                    'email' => 'ul_jgomez@gmail.com',
                                    'reason' => '550 5.1.1 The email account that you tried to reach does not exist. NoSuchUser',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $inspection = app(NotificationDispatchFailureInspector::class)->inspect($log);

    expect($inspection['failures'])->toHaveCount(1)
        ->and($inspection['failures'][0]['address'])->toBe('ul_jgomez@gmail.com')
        ->and($inspection['failures'][0]['failure_code'])->toBe('email_address_not_found')
        ->and($inspection['failures'][0]['analyst_message'])->toContain('no existe')
        ->and($inspection['failures'][0]['resolution_steps'])->toContain('tipeo');
});

test('retry service resends only failed emails for a mass notification', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 3,
            'total' => 3,
        ], 200),
    ]);

    $user = dispatchRetryUser([
        MarketingPermission::ViewNotificationLogs,
        MarketingPermission::ManageMassNotifications,
    ]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'Mensaje de prueba',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->getKey(),
    ]);
    $log = failedEmailDispatchLog($user, $notification);

    $result = app(NotificationDispatchRetryService::class)->retry($log, $user);

    expect($result->successful)->toBeTrue()
        ->and($result->sent)->toBe(3)
        ->and($result->total)->toBe(3);

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && str_contains($body, 'aaular@tudrencasa.com')
            && str_contains($body, 'gcamacho@tudrencasa.com')
            && str_contains($body, 'marketing@tudrencasa.com');
    });

    app()->terminate();

    expect(NotificationDispatchLog::query()->count())->toBeGreaterThan(1);
});

test('view page shows progress section and can retry failed dispatch', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 3,
            'total' => 3,
        ], 200),
    ]);

    $user = dispatchRetryUser([
        MarketingPermission::ViewNotificationLogs,
        MarketingPermission::ManageMassNotifications,
        MarketingPermission::ViewMassNotifications,
    ]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'Mensaje de prueba',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->getKey(),
    ]);
    $log = failedEmailDispatchLog($user, $notification);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewNotificationDispatchLog::class, ['record' => $log->getKey()])
        ->assertOk()
        ->assertSee('Progreso del envío')
        ->assertSee('aaular@tudrencasa.com')
        ->assertSee('read ECONNRESET')
        ->assertActionVisible('retryDispatch')
        ->callAction('retryDispatch')
        ->assertNotified();

    Http::assertSentCount(1);
});

test('analyst without manage permission cannot retry', function () {
    $user = dispatchRetryUser([
        MarketingPermission::ViewNotificationLogs,
    ]);
    $notification = MassNotification::factory()->create([
        'title' => 'prueba 19',
        'copy' => 'Mensaje de prueba',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'created_by_id' => $user->getKey(),
    ]);
    $log = failedEmailDispatchLog($user, $notification);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewNotificationDispatchLog::class, ['record' => $log->getKey()])
        ->assertOk()
        ->assertActionHidden('retryDispatch');
});
