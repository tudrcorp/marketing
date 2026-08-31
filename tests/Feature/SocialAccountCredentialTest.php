<?php

use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

use App\Filament\Resources\SocialAccounts\Pages\EditSocialAccount;
use App\Filament\Resources\SocialAccounts\Pages\ListSocialAccounts;
use App\Marketing\MarketingPermission;
use App\Marketing\SocialPlatform;
use App\Models\MarketingRole;
use App\Models\SocialAccount;
use App\Models\User;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function credentialUserWithRole(string $slug, array $permissions = []): User
{
    $role = MarketingRole::query()->where('slug', $slug)->first()
        ?? MarketingRole::factory()->create([
            'slug' => $slug,
            'permissions' => $permissions,
        ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('social account password is stored encrypted in database', function () {
    $account = SocialAccount::factory()->create([
        'platform' => SocialPlatform::Instagram,
        'account_password' => 'Clave-Segura-123',
    ]);

    $rawPassword = $account->getRawOriginal('account_password');

    expect($rawPassword)
        ->not->toBe('Clave-Segura-123')
        ->and(Crypt::decryptString($rawPassword))->toBe('Clave-Segura-123')
        ->and($account->fresh()->account_password)->toBe('Clave-Segura-123');
});

test('administrator can view social account password action', function () {
    $admin = credentialUserWithRole('administrador');

    $account = SocialAccount::factory()->create([
        'account_password' => 'MiClaveAdmin',
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel('marketing');

    expect($admin->can('viewPassword', $account))->toBeTrue();

    Livewire::test(ListSocialAccounts::class)
        ->assertOk()
        ->assertTableActionVisible('viewAccountPassword', $account);
});

test('analyst cannot view social account password action', function () {
    $analyst = credentialUserWithRole('analista');

    $account = SocialAccount::factory()->create([
        'account_password' => 'MiClaveAnalista',
    ]);

    $this->actingAs($analyst);
    Filament::setCurrentPanel('marketing');

    expect($analyst->can('viewPassword', $account))->toBeFalse();

    Livewire::test(ListSocialAccounts::class)
        ->assertOk()
        ->assertTableActionHidden('viewAccountPassword', $account);
});

test('analyst cannot edit social account handle or password fields', function () {
    $analyst = credentialUserWithRole('analista', [
        MarketingPermission::ViewSocialAccounts,
        MarketingPermission::ManageSocialAccounts,
    ]);

    $account = SocialAccount::factory()->create([
        'handle' => '@cuenta-original',
        'account_password' => 'Clave-Original',
    ]);

    $this->actingAs($analyst);
    Filament::setCurrentPanel('marketing');

    expect($analyst->can('manageCredentials', $account))->toBeFalse();

    Livewire::test(EditSocialAccount::class, ['record' => $account->id])
        ->assertFormFieldDisabled('handle')
        ->assertFormFieldDoesNotExist('account_password')
        ->assertSee('Solo un administrador de marketing puede registrar o actualizar el usuario y la clave de esta cuenta.');
});

test('analyst cannot override handle or password through save mutation', function () {
    $analyst = credentialUserWithRole('analista', [
        MarketingPermission::ViewSocialAccounts,
        MarketingPermission::ManageSocialAccounts,
    ]);

    $account = SocialAccount::factory()->create([
        'name' => 'Cuenta protegida',
        'handle' => '@cuenta-original',
        'account_password' => 'Clave-Original',
    ]);

    $this->actingAs($analyst);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditSocialAccount::class, ['record' => $account->id])
        ->fillForm([
            'name' => 'Cuenta actualizada por analista',
            'platform' => $account->platform->value,
            'handle' => '@handle-manipulado',
            'account_password' => 'Clave-Manipulada',
            'profile_url' => $account->profile_url,
            'is_active' => true,
            'notes' => $account->notes,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($account->fresh())
        ->name->toBe('Cuenta actualizada por analista')
        ->handle->toBe('@cuenta-original')
        ->account_password->toBe('Clave-Original');
});

test('administrator can edit social account handle and password', function () {
    $admin = credentialUserWithRole('administrador');

    $account = SocialAccount::factory()->create([
        'handle' => '@cuenta-admin',
        'account_password' => 'Clave-Anterior',
    ]);

    $this->actingAs($admin);
    Filament::setCurrentPanel('marketing');

    expect($admin->can('manageCredentials', $account))->toBeTrue();

    Livewire::test(EditSocialAccount::class, ['record' => $account->id])
        ->assertFormFieldEnabled('handle')
        ->assertFormFieldExists('account_password')
        ->fillForm([
            'name' => $account->name,
            'platform' => $account->platform->value,
            'handle' => '@cuenta-actualizada',
            'account_password' => 'Clave-Nueva',
            'profile_url' => $account->profile_url,
            'is_active' => true,
            'notes' => $account->notes,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($account->fresh())
        ->handle->toBe('@cuenta-actualizada')
        ->account_password->toBe('Clave-Nueva');
});

test('editing social account without changing password keeps existing credential', function () {
    $analyst = credentialUserWithRole('analista', [
        MarketingPermission::ViewSocialAccounts,
        MarketingPermission::ManageSocialAccounts,
    ]);

    $account = SocialAccount::factory()->create([
        'name' => 'Cuenta original',
        'account_password' => 'Clave-Original',
    ]);

    $this->actingAs($analyst);
    Filament::setCurrentPanel('marketing');

    Livewire::test(EditSocialAccount::class, ['record' => $account->id])
        ->fillForm([
            'name' => 'Cuenta actualizada',
            'platform' => $account->platform->value,
            'handle' => $account->handle,
            'profile_url' => $account->profile_url,
            'is_active' => true,
            'notes' => $account->notes,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($account->fresh())
        ->name->toBe('Cuenta actualizada')
        ->account_password->toBe('Clave-Original');
});
