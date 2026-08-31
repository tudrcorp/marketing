<?php

use App\Jobs\SendCorporateEventInvitationEmailJob;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\CorporateEvent;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\CorporateEventRegistrationShareService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('registration share service dispatches the invitation email job on the email queue', function () {
    Queue::fake([SendCorporateEventInvitationEmailJob::class]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create();

    $result = app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::Email,
        message: 'Mensaje personalizado del analista.',
        email: 'Invitado@Example.com',
        phone: null,
        sentBy: $user,
    );

    expect($result->successful)->toBeTrue()
        ->and($result->queued)->toBeTrue();

    Queue::assertPushedOn('email', SendCorporateEventInvitationEmailJob::class);
    Queue::assertPushed(SendCorporateEventInvitationEmailJob::class, function (SendCorporateEventInvitationEmailJob $job) use ($event, $user): bool {
        return $job->corporateEventId === $event->getKey()
            && $job->email === 'invitado@example.com'
            && $job->sentByName === $user->name
            && $job->sentById === $user->getKey();
    });
});

test('corporate event invitation email job posts rendered html to bulk endpoint', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create([
        'title' => 'Evento de capacitación',
    ]);

    (new SendCorporateEventInvitationEmailJob(
        corporateEventId: $event->getKey(),
        email: 'invitado@example.com',
        message: 'Mensaje personalizado del analista.',
        sentByName: $user->name,
        sentById: $user->getKey(),
    ))->handle(
        app(\App\Services\Marketing\MarketingApiTraceRecorder::class),
        app(\App\Services\Marketing\NotificationDispatchLogger::class),
    );

    Http::assertSent(function ($request) use ($event): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && str_contains($body, 'invitado@example.com')
            && str_contains($body, 'Mensaje personalizado del analista.')
            && str_contains($body, 'Invitación: '.$event->title);
    });

    $log = NotificationDispatchLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('sent')
        ->and($log->sent_count)->toBe(1)
        ->and($log->corporate_event_id)->toBe($event->getKey());
});

test('registration share service sends email through marketing api bulk endpoint', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create([
        'title' => 'Evento de capacitación',
    ]);

    $result = app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::Email,
        message: 'Mensaje personalizado del analista.',
        email: 'invitado@example.com',
        phone: null,
        sentBy: $user,
    );

    Http::assertSent(function ($request) use ($event): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && $request->isMultipart()
            && $request->hasFile('logo', null, 'logoTDG.png')
            && str_contains($body, 'invitado@example.com')
            && str_contains($body, 'cid:company-logo')
            && str_contains($body, 'Mensaje personalizado del analista.')
            && str_contains($body, 'Inscribirme ahora')
            && str_contains($body, $event->registration_url)
            && str_contains($body, 'Invitación: '.$event->title);
    });

    expect($result->successful)->toBeTrue()
        ->and($result->queued)->toBeTrue()
        ->and($result->message)->toContain('invitado@example.com');

    $log = NotificationDispatchLog::query()->where('status', 'sent')->first();

    expect($log)->not->toBeNull()
        ->and($log->sent_count)->toBe(1)
        ->and($log->summary)->toContain('invitado@example.com');
});

test('registration share service attaches cover image when sending email', function () {
    Storage::fake('public');
    Storage::disk('public')->put('corporate-events/covers/evento.jpg', 'cover-image-bytes');

    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create([
        'cover_image' => 'corporate-events/covers/evento.jpg',
    ]);

    $result = app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::Email,
        message: 'Mensaje con imagen de portada.',
        email: 'invitado@example.com',
        phone: null,
        sentBy: $user,
    );

    Http::assertSent(function ($request): bool {
        return str_ends_with($request->url(), '/api/emails/bulk')
            && $request->hasFile('image', null, 'evento.jpg')
            && str_contains($request->body(), 'cid:campaign-image');
    });

    expect($result->successful)->toBeTrue();
});

test('registration share service sends whatsapp through marketing api', function () {
    Http::fake([
        '*/api/notifications/birthday/test' => Http::response(['message' => 'ok'], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create();

    $result = app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::WhatsApp,
        message: 'Inscríbete al evento corporativo.',
        email: null,
        phone: '+58 412 0000000',
        sentBy: $user,
    );

    Http::assertSent(function ($request) use ($event, $user): bool {
        return $request->url() === 'http://localhost:4000/api/notifications/birthday/test'
            && $request['channel'] === BirthdayNotificationChannel::WhatsApp->value
            && $request['phone'] === '+584120000000'
            && $request['copy'] === 'Inscríbete al evento corporativo.'
            && $request['image_url'] === null
            && $request['notification_id'] === $event->getKey()
            && $request['sent_by_id'] === $user->getKey()
            && $request['is_test'] === false;
    });

    expect($result->successful)->toBeTrue();
});

test('registration share service sends whatsapp with cover image when available', function () {
    Storage::fake('public');
    Storage::disk('public')->put('corporate-events/covers/evento.jpg', 'cover-image-bytes');

    Http::fake([
        '*/api/notifications/birthday/test' => Http::response(['message' => 'ok'], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create([
        'cover_image' => 'corporate-events/covers/evento.jpg',
    ]);

    $expectedImageUrl = Storage::disk('public')->url('corporate-events/covers/evento.jpg');

    $result = app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::WhatsApp,
        message: 'Inscríbete al evento corporativo.',
        email: null,
        phone: '+58 412 0000000',
        sentBy: $user,
    );

    Http::assertSent(function ($request) use ($expectedImageUrl, $event, $user): bool {
        return $request->url() === 'http://localhost:4000/api/notifications/birthday/test'
            && $request['channel'] === BirthdayNotificationChannel::WhatsApp->value
            && $request['image_url'] === $expectedImageUrl
            && $request['notification_id'] === $event->getKey()
            && $request['sent_by_id'] === $user->getKey();
    });

    expect($result->successful)->toBeTrue();
});

test('registration share service sends to multiple channels at once', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
        '*/api/notifications/birthday/test' => Http::response(['message' => 'ok'], 200),
    ]);

    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create();

    $result = app(CorporateEventRegistrationShareService::class)->sendToChannels(
        event: $event,
        channels: [
            BirthdayNotificationChannel::Email->value,
            BirthdayNotificationChannel::WhatsApp->value,
            BirthdayNotificationChannel::Sms->value,
        ],
        message: 'Inscríbete al evento corporativo.',
        email: 'invitado@example.com',
        phone: '+58 412 0000000',
        sentBy: $user,
    );

    Http::assertSentCount(3);

    expect($result->allSuccessful())->toBeTrue()
        ->and($result->hasQueued())->toBeTrue()
        ->and($result->results)->toHaveCount(3)
        ->and($result->summary())->toContain('Correo electrónico: en cola')
        ->and($result->summary())->toContain('WhatsApp: enviado')
        ->and($result->summary())->toContain('SMS: enviado');
});

test('registration share service requires email for email channel', function () {
    $user = User::factory()->create();
    $event = CorporateEvent::factory()->published()->create();

    $result = app(CorporateEventRegistrationShareService::class)->send(
        event: $event,
        channel: BirthdayNotificationChannel::Email,
        message: 'Mensaje de invitación.',
        email: null,
        phone: null,
        sentBy: $user,
    );

    expect($result->successful)->toBeFalse()
        ->and($result->message)->toBe('Debes indicar un correo electrónico.');
});
