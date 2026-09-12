<?php

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Marketing\MarketingPermission;
use App\Models\Brand;
use App\Models\MarketingRole;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('marketing');
});

function brandUser(array $permissions): User
{
    $role = MarketingRole::factory()->create(['permissions' => $permissions]);

    return User::factory()->create(['marketing_role_id' => $role->id]);
}

test('el listado de marcas está disponible para quien puede verlas', function () {
    Brand::factory()->count(3)->create();

    Livewire::actingAs(brandUser([MarketingPermission::ViewBrands]))
        ->test(ListBrands::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Brand::query()->get());
});

test('crear una marca guarda la bóveda de recursos y el autor', function () {
    $user = brandUser([MarketingPermission::ViewBrands, MarketingPermission::ManageBrands]);

    Livewire::actingAs($user)
        ->test(CreateBrand::class)
        ->fillForm([
            'name' => 'TDG Bienestar',
            'slug' => 'tdg-bienestar',
            'color_hex' => '#34d399',
            'brand_voice' => 'Cálida y motivadora.',
            'is_active' => true,
            'ctas' => [['label' => 'Comunidad', 'value' => 'Únete a la comunidad TDG']],
            'hashtag_groups' => [['label' => 'Generales', 'value' => '#TDGBienestar']],
            'quick_links' => [['label' => 'Blog', 'value' => 'https://tudoctorgroup.com/blog']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $brand = Brand::query()->firstWhere('slug', 'tdg-bienestar');

    expect($brand)->not->toBeNull()
        ->and($brand->created_by_id)->toBe($user->id)
        ->and($brand->vaultCtas())->toHaveCount(1)
        ->and($brand->vaultCtas()[0]['value'])->toBe('Únete a la comunidad TDG')
        ->and($brand->initials())->toBe('TB');
});

test('sin permiso de gestión no se puede crear una marca', function () {
    $viewer = brandUser([MarketingPermission::ViewBrands]);

    expect(Gate::forUser($viewer)->check('create', Brand::class))->toBeFalse();
});
