<?php

use App\Filament\Resources\MassNotifications\Pages\CreateMassNotification;
use App\Filament\Resources\MassNotifications\Pages\ListMassNotifications;
use App\Filament\Resources\MassNotifications\Pages\ViewMassNotification;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Marketing\MassNotificationContentType;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\User;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function massNotificationUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('analyst can list mass notifications', function () {
    $user = massNotificationUserWithPermissions([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);

    MassNotification::factory()->create([
        'title' => 'Promoción verano',
        'copy' => 'Aprovecha nuestra promoción especial de verano.',
        'channels' => [BirthdayNotificationChannel::WhatsApp->value, BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::BrokerAgents->value],
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ListMassNotifications::class)
        ->assertOk()
        ->assertSee('Promoción verano')
        ->assertSee('WhatsApp');
});

test('analyst can create a mass notification with required fields and optional content type', function () {
    $user = massNotificationUserWithPermissions([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(CreateMassNotification::class)
        ->fillForm([
            'title' => 'Campaña TDG Q2',
            'copy' => 'Tu Doctor Group tiene una nueva promoción para ti.',
            'channels' => [
                BirthdayNotificationChannel::Email->value,
                BirthdayNotificationChannel::Sms->value,
            ],
            'audiences' => [
                BirthdayNotificationAudience::Collaborators->value,
                BirthdayNotificationAudience::IndividualAffiliates->value,
            ],
            'content_type' => MassNotificationContentType::Image->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $notification = MassNotification::query()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Campaña TDG Q2')
        ->and($notification->copy)->toBe('Tu Doctor Group tiene una nueva promoción para ti.')
        ->and($notification->channels)->toBe([
            BirthdayNotificationChannel::Email->value,
            BirthdayNotificationChannel::Sms->value,
        ])
        ->and($notification->audiences)->toBe([
            BirthdayNotificationAudience::Collaborators->value,
            BirthdayNotificationAudience::IndividualAffiliates->value,
        ])
        ->and($notification->content_type)->toBe(MassNotificationContentType::Image->value)
        ->and($notification->created_by_id)->toBe($user->id);
});

test('analyst can view mass notification details', function () {
    $user = massNotificationUserWithPermissions([
        MarketingPermission::ViewMassNotifications,
    ]);

    $notification = MassNotification::factory()->create([
        'title' => 'Aviso importante',
        'copy' => 'Mensaje informativo para todos los afiliados.',
        'channels' => [BirthdayNotificationChannel::Sms->value],
        'audiences' => [BirthdayNotificationAudience::CorporateAffiliates->value],
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewMassNotification::class, ['record' => $notification->getKey()])
        ->assertOk()
        ->assertSee('Aviso importante')
        ->assertSee('Mensaje informativo para todos los afiliados.')
        ->assertSee('SMS')
        ->assertSee('Afiliados corporativos');
});

test('analyst must select at least one audience group when creating a mass notification', function () {
    $user = massNotificationUserWithPermissions([
        MarketingPermission::ViewMassNotifications,
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(CreateMassNotification::class)
        ->fillForm([
            'title' => 'Campaña sin grupos',
            'copy' => 'Mensaje de prueba sin audiencias.',
            'channels' => [BirthdayNotificationChannel::Email->value],
            'audiences' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['audiences']);
});

test('user without permission cannot access mass notifications list', function () {
    $user = massNotificationUserWithPermissions([
        MarketingPermission::ViewCalendar,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ListMassNotifications::class)
        ->assertForbidden();
});
