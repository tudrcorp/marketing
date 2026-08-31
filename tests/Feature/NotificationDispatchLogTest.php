<?php

use App\Jobs\PersistNotificationDispatchLogJob;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\CorporateEvent;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\CorporateEventRegistrationShareService;
use App\Services\Marketing\NotificationDispatchFailureResolver;
use App\Services\Marketing\NotificationDispatchLogData;
use App\Services\Marketing\NotificationDispatchLogger;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('failure resolver translates api connection errors for analysts', function () {
    $guidance = app(NotificationDispatchFailureResolver::class)->resolve(
        status: NotificationDispatchStatus::Failed,
        technicalMessage: 'No se pudo conectar con el API de mensajería.',
        channel: BirthdayNotificationChannel::WhatsApp,
    );

    expect($guidance['failure_code'])->toBe('api_messaging_unreachable')
        ->and($guidance['analyst_message'])->toContain('WhatsApp')
        ->and($guidance['resolution_steps'])->toContain('dashboard');
});

test('failure resolver translates smtp connection resets for analysts', function () {
    $guidance = app(NotificationDispatchFailureResolver::class)->resolve(
        status: NotificationDispatchStatus::Failed,
        technicalMessage: 'No se pudo enviar ningún correo: read ECONNRESET',
        channel: BirthdayNotificationChannel::Email,
    );

    expect($guidance['failure_code'])->toBe('smtp_connection_reset')
        ->and($guidance['analyst_message'])->toContain('Gmail/SMTP')
        ->and($guidance['resolution_steps'])->toContain('contraseña de aplicación');
});

test('failure resolver translates gmail address not found bounce for analysts', function () {
    $guidance = app(NotificationDispatchFailureResolver::class)->resolve(
        status: NotificationDispatchStatus::Failed,
        technicalMessage: '550 5.1.1 The email account that you tried to reach does not exist. https://support.google.com/mail/?p=NoSuchUser',
        channel: BirthdayNotificationChannel::Email,
    );

    expect($guidance['failure_code'])->toBe('email_address_not_found')
        ->and($guidance['analyst_message'])->toContain('no existe')
        ->and($guidance['resolution_steps'])->toContain('tipeo');
});

test('failure resolver treats gmail bandwidth lock as the same pause as daily quota', function () {
    $guidance = app(NotificationDispatchFailureResolver::class)->resolve(
        status: NotificationDispatchStatus::Failed,
        technicalMessage: 'Límite de ancho de banda de Gmail para las cargas por medio de la Web',
        channel: BirthdayNotificationChannel::Email,
    );

    expect($guidance['failure_code'])->toBe('email_quota_exceeded')
        ->and($guidance['analyst_message'])->toContain('ancho de banda')
        ->and($guidance['resolution_steps'])->toContain('NO relances');
});

test('persist job stores analyst friendly guidance', function () {
    $user = User::factory()->create();

    PersistNotificationDispatchLogJob::dispatchSync([
        'source' => NotificationDispatchSource::CorporateShare->value,
        'status' => NotificationDispatchStatus::Failed->value,
        'title' => 'Invitación: Evento demo',
        'summary' => 'Debes indicar un número de teléfono.',
        'channel' => BirthdayNotificationChannel::WhatsApp->value,
        'technicalMessage' => 'Debes indicar un número de teléfono.',
        'sentCount' => 0,
        'totalCount' => 1,
        'sentById' => $user->getKey(),
    ]);

    $log = NotificationDispatchLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->failure_code)->toBe('missing_recipient')
        ->and($log->analyst_message)->toContain('destinatario')
        ->and($log->resolution_steps)->toContain('correo o teléfono');
});

test('share service creates dispatch log after failed whatsapp send', function () {
    Http::fake([
        '*/api/notifications/birthday/test' => Http::response(['reason' => 'file not exist'], 422),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create();

    app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::WhatsApp,
        message: 'Inscríbete hoy.',
        email: null,
        phone: '+58 412 0000000',
        sentBy: $user,
    );

    app()->terminate();

    $log = NotificationDispatchLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->source)->toBe(NotificationDispatchSource::CorporateShare->value)
        ->and($log->status)->toBe(NotificationDispatchStatus::Failed->value)
        ->and($log->corporate_event_id)->toBe($event->getKey())
        ->and($log->analyst_message)->not->toBeEmpty()
        ->and($log->resolution_steps)->not->toBeEmpty()
        ->and($log->technical_detail)->toBeArray()
        ->and($log->technical_detail['api_calls'])->toHaveCount(1)
        ->and($log->technical_detail['api_calls'][0]['response']['status'])->toBe(422)
        ->and($log->technical_detail['api_calls'][0]['response']['body'])->toMatchArray(['reason' => 'file not exist']);
});

test('share service stores successful whatsapp api response in technical detail', function () {
    Http::fake([
        '*/api/notifications/birthday/test' => Http::response([
            'success' => true,
            'message' => 'queued',
            'provider' => 'ultramsg',
        ], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create();

    app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::WhatsApp,
        message: 'Inscríbete hoy.',
        email: null,
        phone: '+58 412 0000000',
        sentBy: $user,
    );

    app()->terminate();

    $log = NotificationDispatchLog::query()->first();

    expect($log->status)->toBe(NotificationDispatchStatus::Sent->value)
        ->and($log->technical_detail['api_calls'][0]['response']['status'])->toBe(200)
        ->and($log->technical_detail['api_calls'][0]['response']['body']['success'])->toBeTrue();
});

test('logger records successful channel result', function () {
    $user = User::factory()->create();

    app(NotificationDispatchLogger::class)->logSync(new NotificationDispatchLogData(
        source: NotificationDispatchSource::BirthdayTest,
        status: NotificationDispatchStatus::Sent,
        title: '[Prueba] Feliz cumpleaños',
        summary: 'Correo enviado a invitado@example.com.',
        channel: BirthdayNotificationChannel::Email,
        technicalMessage: 'Correo enviado a invitado@example.com.',
        sentCount: 1,
        totalCount: 1,
        recipient: 'invitado@example.com',
        sentById: $user->getKey(),
    ));

    $log = NotificationDispatchLog::query()->first();

    expect($log->status)->toBe(NotificationDispatchStatus::Sent->value)
        ->and($log->failure_code)->toBe('smtp_accepted')
        ->and($log->analyst_message)->toContain('aceptó el mensaje')
        ->and($log->resolution_steps)->toContain('550 5.1.1');
});

test('failure resolver maps smtp 550 mailbox not found for analysts', function () {
    $guidance = app(NotificationDispatchFailureResolver::class)->resolve(
        status: NotificationDispatchStatus::Failed,
        technicalMessage: '550 5.1.1 The email account that you tried to reach does not exist. - gsmtp',
        channel: BirthdayNotificationChannel::Email,
    );

    expect($guidance['failure_code'])->toBe('email_address_not_found')
        ->and($guidance['analyst_message'])->toContain('no existe')
        ->and($guidance['resolution_steps'])->toContain('tipeo');
});
