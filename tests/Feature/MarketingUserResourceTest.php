<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use App\Models\User;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);

    Filament::setCurrentPanel('marketing');
});

function panelUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('administrator can register a panel user with a marketing role', function () {
    $admin = panelUserWithPermissions(MarketingPermission::all());
    $analystRole = MarketingRole::query()->where('slug', 'analista')->firstOrFail();

    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->assertOk()
        ->fillForm([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@tudrencasa.com',
            'marketing_role_id' => $analystRole->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'ana.perez@tudrencasa.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->name)->toBe('Ana Pérez')
        ->and($created->marketing_role_id)->toBe($analystRole->id)
        ->and($created->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $created->password))->toBeTrue()
        ->and($created->canAccessPanel(Filament::getPanel('marketing')))->toBeTrue();
});

test('administrator can list panel users', function () {
    $admin = panelUserWithPermissions(MarketingPermission::all());

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertSee('Usuarios del panel')
        ->assertSee('Nuevo usuario')
        ->assertSee($admin->name);
});

test('analyst cannot access the users resource', function () {
    $analyst = panelUserWithPermissions([
        MarketingPermission::ViewPublications,
        MarketingPermission::ManagePublications,
    ]);

    $this->actingAs($analyst);

    Livewire::test(ListUsers::class)
        ->assertForbidden();

    Livewire::test(CreateUser::class)
        ->assertForbidden();
});

test('administrator can update a user role and leave the password unchanged', function () {
    $admin = panelUserWithPermissions(MarketingPermission::all());
    $viewerRole = MarketingRole::query()->where('slug', 'visor')->firstOrFail();
    $user = User::factory()->create([
        'name' => 'Luis Gómez',
        'email' => 'luis@tudrencasa.com',
        'password' => 'password',
        'marketing_role_id' => MarketingRole::query()->where('slug', 'analista')->value('id'),
    ]);
    $originalHash = $user->password;

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => 'Luis Gómez',
            'email' => 'luis@tudrencasa.com',
            'marketing_role_id' => $viewerRole->id,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh())
        ->marketing_role_id->toBe($viewerRole->id)
        ->password->toBe($originalHash);
});

test('administrator cannot delete the last marketing administrator', function () {
    $adminRole = MarketingRole::query()->where('slug', 'administrador')->firstOrFail();
    $admin = User::factory()->create([
        'marketing_role_id' => $adminRole->id,
    ]);
    $otherAdmin = User::factory()->create([
        'marketing_role_id' => $adminRole->id,
    ]);

    $this->actingAs($admin);

    expect($admin->can('delete', $otherAdmin))->toBeTrue();

    $otherAdmin->delete();

    expect($admin->fresh()->isLastMarketingAdministrator())->toBeTrue()
        ->and($admin->can('delete', $admin))->toBeFalse();
});
