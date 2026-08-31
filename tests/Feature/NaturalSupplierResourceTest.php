<?php

use App\Filament\Resources\NaturalSuppliers\Pages\ManageNaturalSuppliers;
use App\Filament\Resources\NaturalSuppliers\Pages\ViewNaturalSupplier;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the natural suppliers list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/doctor-nurses*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 146,
                    'name' => 'adriana  perez',
                    'rif' => '',
                    'razon_social' => 'adriana  perez',
                    'personal_phone' => '4246892744',
                    'local_phone' => '',
                    'correo_principal' => 'adrianaperezlamoine@hotmail.com',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageNaturalSuppliers::class)
        ->assertSee('Directorio de proveedores naturales')
        ->assertSee('ADRIANA  PEREZ')
        ->assertSee('4246892744')
        ->assertSee('adrianaperezlamoine@hotmail.com');
});

it('renders the natural supplier infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/doctor-nurses*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 146,
                    'name' => 'adriana  perez',
                    'rif' => '',
                    'razon_social' => 'adriana  perez',
                    'personal_phone' => '4246892744',
                    'local_phone' => '',
                    'correo_principal' => 'adrianaperezlamoine@hotmail.com',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewNaturalSupplier::class, ['record' => '146'])
        ->assertSee('ADRIANA  PEREZ')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('adrianaperezlamoine@hotmail.com');
});

it('requires authentication to access the natural suppliers list', function () {
    $this->get(route('filament.marketing.resources.proveedores-naturales.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
