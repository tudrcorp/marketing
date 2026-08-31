<?php

use App\Filament\Resources\BirthdayNotifications\Pages\ViewBirthdayNotification;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Models\BirthdayNotification;
use App\Models\MarketingRole;
use App\Models\User;
use App\Services\Marketing\BirthdayNotificationTestSendService;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function testSendUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('test send service sends email through marketing api bulk endpoint', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = User::factory()->create();
    $notification = BirthdayNotification::factory()->create([
        'name' => 'Cumpleaños TDG',
        'copy' => '¡Feliz cumpleaños!',
        'channels' => [BirthdayNotificationChannel::Email->value],
    ]);

    $result = app(BirthdayNotificationTestSendService::class)->send(
        notification: $notification,
        channels: [BirthdayNotificationChannel::Email->value],
        email: 'prueba@example.com',
        phone: null,
        sentBy: $user,
    );

    Http::assertSent(function ($request) use ($notification): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && $request->isMultipart()
            && $request->hasFile('logo', null, 'logoTDG.png')
            && str_contains($body, 'prueba@example.com')
            && str_contains($body, 'cid:company-logo')
            && str_contains($body, '¡Feliz cumpleaños!')
            && str_contains($body, 'Tu Doctor Group')
            && str_contains($body, 'Envío de prueba.')
            && str_contains($body, '[Prueba] '.$notification->name);
    });

    expect($result->allSuccessful())->toBeTrue();
});

test('test send service sends whatsapp through marketing api', function () {
    Http::fake([
        '*/api/notifications/birthday/test' => Http::response(['success' => true, 'message' => 'ok'], 200),
    ]);

    $user = User::factory()->create();
    $notification = BirthdayNotification::factory()->create([
        'copy' => '¡Feliz cumpleaños!',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    $result = app(BirthdayNotificationTestSendService::class)->send(
        notification: $notification,
        channels: [BirthdayNotificationChannel::WhatsApp->value],
        email: null,
        phone: '04141234567',
        sentBy: $user,
    );

    Http::assertSent(function ($request): bool {
        return $request->url() === 'http://localhost:4000/api/notifications/birthday/test'
            && $request['channel'] === BirthdayNotificationChannel::WhatsApp->value
            && $request['phone'] === '584141234567'
            && $request['is_test'] === true;
    });

    expect($result->allSuccessful())->toBeTrue();
});

test('test send service sends whatsapp image url for birthday notifications', function () {
    Http::fake([
        '*/api/notifications/birthday/test' => Http::response(['success' => true, 'message' => 'ok'], 200),
    ]);

    config(['app.url' => 'https://tdg-marketing.test']);

    $user = User::factory()->create();
    $notification = BirthdayNotification::factory()->create([
        'copy' => '¡Feliz cumpleaños!',
        'image' => 'birthday-notifications/images/sample.png',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
    ]);

    app(BirthdayNotificationTestSendService::class)->send(
        notification: $notification,
        channels: [BirthdayNotificationChannel::WhatsApp->value],
        email: null,
        phone: '04141234567',
        sentBy: $user,
    );

    Http::assertSent(function ($request): bool {
        return filled($request['image_url'] ?? null)
            && str_contains($request['image_url'], 'birthday-notifications/images/sample.png');
    });
});

test('analyst can send test birthday notification from view page', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = testSendUserWithPermissions([
        MarketingPermission::ViewBirthdayNotifications,
        MarketingPermission::ManageBirthdayNotifications,
    ]);

    $notification = BirthdayNotification::factory()->create([
        'copy' => '¡Feliz cumpleaños de parte de Tu Doctor Group!',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewBirthdayNotification::class, ['record' => $notification->getKey()])
        ->callAction('sendTestBirthdayNotification', data: [
            'test_channels' => [BirthdayNotificationChannel::Email->value],
            'email' => 'analista@example.com',
        ])
        ->assertNotified();

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/emails/bulk'));
});

test('user without manage permission cannot see test send action', function () {
    $user = testSendUserWithPermissions([
        MarketingPermission::ViewBirthdayNotifications,
    ]);

    $notification = BirthdayNotification::factory()->create([
        'copy' => '¡Feliz cumpleaños!',
        'channels' => [BirthdayNotificationChannel::Email->value],
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewBirthdayNotification::class, ['record' => $notification->getKey()])
        ->assertActionHidden('sendTestBirthdayNotification');
});
