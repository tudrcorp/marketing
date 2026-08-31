<?php

use App\Filament\Resources\BirthdayNotifications\Pages\CreateBirthdayNotification;
use App\Filament\Resources\BirthdayNotifications\Pages\ListBirthdayNotifications;
use App\Filament\Resources\BirthdayNotifications\Pages\ViewBirthdayNotification;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Models\BirthdayNotification;
use App\Models\MarketingRole;
use App\Models\User;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function birthdayNotificationUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('analyst can list birthday notifications', function () {
    $user = birthdayNotificationUserWithPermissions([
        MarketingPermission::ViewBirthdayNotifications,
        MarketingPermission::ManageBirthdayNotifications,
    ]);

    BirthdayNotification::factory()->create([
        'name' => 'Felicitación marzo',
        'copy' => '¡Feliz cumpleaños! Que tengas un excelente día.',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value, BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::Collaborators->value],
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ListBirthdayNotifications::class)
        ->assertOk()
        ->assertSee('Felicitación marzo')
        ->assertSee('WhatsApp')
        ->assertSee('Colaboradores');
});

test('analyst can create a birthday notification with multiple channels and audiences', function () {
    $user = birthdayNotificationUserWithPermissions([
        MarketingPermission::ViewBirthdayNotifications,
        MarketingPermission::ManageBirthdayNotifications,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(CreateBirthdayNotification::class)
        ->fillForm([
            'name' => 'Campaña cumpleaños Q2',
            'copy' => 'Tu Doctor Group te desea un feliz cumpleaños.',
            'channels' => [
                BirthdayNotificationChannel::Email->value,
                BirthdayNotificationChannel::Sms->value,
            ],
            'audiences' => [
                BirthdayNotificationAudience::Doctors->value,
                BirthdayNotificationAudience::LegalSuppliers->value,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $notification = BirthdayNotification::query()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->name)->toBe('Campaña cumpleaños Q2')
        ->and($notification->copy)->toBe('Tu Doctor Group te desea un feliz cumpleaños.')
        ->and($notification->channels)->toBe([
            BirthdayNotificationChannel::Email->value,
            BirthdayNotificationChannel::Sms->value,
        ])
        ->and($notification->audiences)->toBe([
            BirthdayNotificationAudience::Doctors->value,
            BirthdayNotificationAudience::LegalSuppliers->value,
        ])
        ->and($notification->created_by_id)->toBe($user->id);
});

test('analyst can view birthday notification infolist', function () {
    $user = birthdayNotificationUserWithPermissions([
        MarketingPermission::ViewBirthdayNotifications,
    ]);

    $notification = BirthdayNotification::factory()->create([
        'name' => 'Felicitación abril',
        'copy' => '¡Feliz cumpleaños de parte de Tu Doctor Group!',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        'audiences' => [
            BirthdayNotificationAudience::Collaborators->value,
            BirthdayNotificationAudience::Doctors->value,
        ],
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewBirthdayNotification::class, ['record' => $notification->getKey()])
        ->assertOk()
        ->assertSee('Felicitación abril')
        ->assertSee('¡Feliz cumpleaños de parte de Tu Doctor Group!')
        ->assertSee('WhatsApp')
        ->assertSee('Colaboradores')
        ->assertSee('Doctores')
        ->assertSee('Vista previa del mensaje')
        ->assertSee('Lista para envío')
        ->assertSee('Volver al listado')
        ->assertActionHidden('sendTestBirthdayNotification')
        ->assertSee('1 canales · 2 audiencias')
        ->assertSee('birthdayNotificationTabs', false)
        ->assertSee('marketing-broker-infolist-tabs', false)
        ->assertSee('marketing-birthday-preview', false);
});

test('user without permission cannot access birthday notifications list', function () {
    $user = birthdayNotificationUserWithPermissions([
        MarketingPermission::ViewCalendar,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ListBirthdayNotifications::class)
        ->assertForbidden();
});
