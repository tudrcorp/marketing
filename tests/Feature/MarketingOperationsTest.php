<?php

use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

use App\Filament\Pages\EditorialCalendar;
use App\Filament\Resources\EditorialPublications\Pages\CreateEditorialPublication;
use App\Filament\Resources\EditorialPublications\Schemas\EditorialPublicationCalendarForm;
use App\Filament\Resources\MarketingRoles\Pages\EditMarketingRole;
use App\Filament\Resources\MarketingRoles\Pages\ListMarketingRoles;
use App\Marketing\MarketingPermission;
use App\Marketing\PublicationStatus;
use App\Marketing\SocialPlatform;
use App\Models\EditorialPublication;
use App\Models\MarketingRole;
use App\Models\SocialAccount;
use App\Models\User;
use Database\Seeders\MarketingAdministratorSeeder;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function marketingUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('marketing administrator seeder creates admin user with marketing role', function () {
    $this->seed(MarketingAdministratorSeeder::class);

    $user = User::query()
        ->where('email', MarketingAdministratorSeeder::ADMIN_EMAIL)
        ->first();

    expect($user)->not->toBeNull()
        ->and($user->marketingRole?->slug)->toBe('administrador')
        ->and($user->hasMarketingPermission(MarketingPermission::ManageRoles))->toBeTrue();
});

test('marketing roles seeder creates system roles', function () {
    expect(MarketingRole::query()->where('slug', 'administrador')->exists())->toBeTrue()
        ->and(MarketingRole::query()->where('slug', 'visor')->exists())->toBeTrue();
});

test('users with a marketing role can access the marketing panel', function () {
    $user = marketingUserWithPermissions([MarketingPermission::ViewCalendar]);

    expect($user->canAccessPanel(Filament::getPanel('marketing')))->toBeTrue();
});

test('users without a marketing role cannot access the marketing panel', function () {
    $user = User::factory()->create(['marketing_role_id' => null]);

    expect($user->canAccessPanel(Filament::getPanel('marketing')))->toBeFalse();
});

test('administrator can list marketing roles with permission counts', function () {
    $user = marketingUserWithPermissions(MarketingPermission::all());

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ListMarketingRoles::class)
        ->assertOk()
        ->assertSee('Administrador de marketing')
        ->assertSee('16 permisos');
});

test('marketing role form renders grouped permissions picker', function () {
    $user = marketingUserWithPermissions(MarketingPermission::all());

    $role = MarketingRole::factory()->create([
        'permissions' => [MarketingPermission::ViewCalendar],
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditMarketingRole::class, ['record' => $role->getKey()])
        ->assertOk()
        ->assertSee('Permisos del rol')
        ->assertSee('Editorial y redes')
        ->assertSee('Notificaciones')
        ->assertSee('Eventos corporativos')
        ->assertSee('Audiencias TDG')
        ->assertSee('Buscar permiso por nombre o módulo');
});

test('administrator can update role permissions after removing deprecated permission keys', function () {
    $user = marketingUserWithPermissions(MarketingPermission::all());

    $role = MarketingRole::factory()->create([
        'permissions' => [
            'campaigns.view',
            MarketingPermission::ViewCalendar,
            MarketingPermission::ViewBirthdayNotifications,
            MarketingPermission::ManageBirthdayNotifications,
        ],
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditMarketingRole::class, ['record' => $role->getKey()])
        ->fillForm([
            'permissions' => [
                MarketingPermission::ViewCalendar,
                MarketingPermission::ViewBirthdayNotifications,
                MarketingPermission::ManageBirthdayNotifications,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $role->refresh();

    expect($role->permissions)->toBe([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewBirthdayNotifications,
        MarketingPermission::ManageBirthdayNotifications,
    ]);
});

test('authorized user can access editorial calendar', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewPublications,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    expect($user->hasMarketingPermission(MarketingPermission::ViewCalendar))->toBeTrue();

    $calendar = Livewire::test(EditorialCalendar::class)
        ->assertOk()
        ->assertSee('Hoy')
        ->assertSee('Día seleccionado')
        ->assertSee('Todas las redes')
        ->assertSee('marketing-agenda__platform-select');

    expect($calendar->instance()->platformFilterOptions)
        ->toHaveCount(5)
        ->and($calendar->instance()->platformFilterOptions[0]['icon'])->toContain('instagram.png')
        ->and($calendar->instance()->platformFilterOptions[1]['icon'])->toContain('youtube.png')
        ->and($calendar->instance()->platformFilterOptions[2]['icon'])->toContain('facebook.png');
});

test('analyst cannot create publication on past day from calendar', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ManagePublications,
    ]);

    $pastDate = now()->subDays(2)->format('Y-m-d');

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    $calendar = Livewire::test(EditorialCalendar::class)
        ->call('selectDay', $pastDate)
        ->assertSet('selectedDate', $pastDate)
        ->assertActionNotMounted('createPublication');

    expect($calendar->instance()->canCreateOnSelectedDate())->toBeFalse();
});

test('analyst selecting an empty day opens create publication modal', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ManagePublications,
    ]);

    $date = now()->addDays(3)->format('Y-m-d');

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditorialCalendar::class)
        ->call('selectDay', $date)
        ->assertSet('selectedDate', $date)
        ->assertSet('calendarMonth', now()->addDays(3)->format('Y-m'))
        ->assertActionMounted('createPublication');
});

test('calendar day cells show social platform icons for scheduled publications', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewPublications,
    ]);

    $date = now()->addDays(5)->startOfDay();
    $instagramAccount = SocialAccount::factory()->create([
        'platform' => SocialPlatform::Instagram,
    ]);
    $facebookAccount = SocialAccount::factory()->create([
        'platform' => SocialPlatform::Facebook,
    ]);

    EditorialPublication::factory()->create([
        'social_account_id' => $instagramAccount->id,
        'scheduled_at' => $date->copy()->setTime(9, 0),
    ]);
    EditorialPublication::factory()->create([
        'social_account_id' => $instagramAccount->id,
        'scheduled_at' => $date->copy()->setTime(12, 0),
    ]);
    EditorialPublication::factory()->create([
        'social_account_id' => $facebookAccount->id,
        'scheduled_at' => $date->copy()->setTime(15, 0),
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    $calendar = Livewire::test(EditorialCalendar::class)
        ->set('calendarMonth', $date->format('Y-m'))
        ->assertSee('marketing-agenda__day-platform-icon');

    $day = collect($calendar->instance()->calendarWeeks)
        ->flatten(1)
        ->firstWhere('date', $date->format('Y-m-d'));

    expect($day)
        ->not->toBeNull()
        ->and($day['count'])->toBe(3)
        ->and($day['platforms'])->toHaveCount(2)
        ->and(collect($day['platforms'])->pluck('value')->all())->toBe(['instagram', 'facebook']);
});

test('analyst selecting a day with publications shows them in workspace', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewPublications,
        MarketingPermission::ManagePublications,
    ]);

    $date = now()->addDays(5)->startOfDay();
    $account = SocialAccount::factory()->create(['name' => 'Cuenta Instagram TDG']);

    EditorialPublication::factory()->count(3)->create([
        'social_account_id' => $account->id,
        'scheduled_at' => $date->copy()->setTime(9, 0),
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditorialCalendar::class)
        ->call('selectDay', $date->format('Y-m-d'))
        ->assertSet('selectedDate', $date->format('Y-m-d'))
        ->assertSee('3 publicaciones programadas')
        ->assertSee('Cuenta Instagram TDG')
        ->assertActionNotMounted('createPublication');
});

test('analyst can edit publication from calendar workspace', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewPublications,
        MarketingPermission::ManagePublications,
    ]);

    $account = SocialAccount::factory()->create();
    $scheduledAt = now()->addDays(4)->setTime(11, 0);

    $publication = EditorialPublication::factory()->create([
        'social_account_id' => $account->id,
        'title' => 'Post original del día',
        'body' => 'Contenido inicial.',
        'scheduled_at' => $scheduledAt,
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditorialCalendar::class)
        ->set('selectedDate', $scheduledAt->format('Y-m-d'))
        ->mountAction('editPublication', ['publication' => $publication->id])
        ->set('mountedActions.0.data', [
            'social_account_id' => $account->id,
            'title' => 'Post actualizado del día',
            'body' => 'Contenido revisado antes de publicar.',
            'reference_image' => null,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'status' => PublicationStatus::Draft->value,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($publication->fresh())
        ->title->toBe('Post actualizado del día')
        ->body->toBe('Contenido revisado antes de publicar.');
});

test('analyst can delete publication from calendar workspace', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewPublications,
        MarketingPermission::ManagePublications,
    ]);

    $publication = EditorialPublication::factory()->create([
        'scheduled_at' => now()->addDays(6)->setTime(15, 30),
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditorialCalendar::class)
        ->set('selectedDate', $publication->scheduled_at->format('Y-m-d'))
        ->mountAction('deletePublication', ['publication' => $publication->id])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(EditorialPublication::query()->find($publication->id))->toBeNull();
});

test('editorial publication calendar form includes reference image field', function () {
    $fieldNames = collect(EditorialPublicationCalendarForm::components())
        ->map(fn ($component): string => $component->getName())
        ->all();

    expect($fieldNames)->toContain('reference_image');
});

test('analyst can schedule publication from calendar action', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewCalendar,
        MarketingPermission::ViewPublications,
        MarketingPermission::ManagePublications,
    ]);

    $account = SocialAccount::factory()->create();
    $scheduledAt = now()->addDays(2)->setTime(10, 30);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditorialCalendar::class)
        ->set('selectedDate', $scheduledAt->format('Y-m-d'))
        ->mountAction('createPublication')
        ->set('mountedActions.0.data', [
            'social_account_id' => $account->id,
            'title' => 'Publicación desde agenda',
            'body' => 'Contenido de prueba para el calendario.',
            'scheduled_at' => $scheduledAt->toDateTimeString(),
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $publication = EditorialPublication::query()
        ->where('title', 'Publicación desde agenda')
        ->first();

    expect($publication)->not->toBeNull()
        ->and($publication->status)->toBe(PublicationStatus::Draft)
        ->and($publication->created_by_id)->toBe($user->id);
});

test('user without permissions cannot access social accounts', function () {
    $user = User::factory()->create(['marketing_role_id' => null]);

    $this->actingAs($user)
        ->get(route('filament.marketing.resources.cuentas-redes.index'))
        ->assertForbidden();
});

test('analyst can create editorial publication', function () {
    $user = marketingUserWithPermissions([
        MarketingPermission::ViewPublications,
        MarketingPermission::ManagePublications,
    ]);

    $account = SocialAccount::factory()->create();

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(CreateEditorialPublication::class)->assertOk();

    expect(EditorialPublication::factory()->create([
        'social_account_id' => $account->id,
        'created_by_id' => $user->id,
        'status' => PublicationStatus::Draft,
    ]))->toBeInstanceOf(EditorialPublication::class);
});

test('approver can schedule publication after approval flow', function () {
    $approver = marketingUserWithPermissions([MarketingPermission::ApprovePublications]);

    $publication = EditorialPublication::factory()->pendingApproval()->create();

    $publication->update([
        'status' => PublicationStatus::Scheduled,
        'approved_by_id' => $approver->id,
        'approved_at' => now(),
    ]);

    expect($publication->fresh())
        ->status->toBe(PublicationStatus::Scheduled)
        ->approved_by_id->toBe($approver->id);
});
