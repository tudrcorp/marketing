<?php

use App\Filament\Resources\TravelAgencies\Pages\ManageTravelAgencies;
use App\Filament\Resources\TravelAgencies\Pages\ViewTravelAgency;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the travel agencies list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 1,
                    'fechaIngreso' => '06/06/2025',
                    'representante' => 'alirio medina',
                    'FechaNacimientoRepresentante' => '27/10/1975',
                    'name' => 'a donde aliRio online',
                    'typeIdentification' => 'J',
                    'numberIdentification' => '296868581',
                    'phone' => '0424 2759838',
                    'phoneAdditional' => '04141336192',
                    'email' => 'productos@adondealirio.com',
                    'userInstagram' => '@adondealirio.online',
                    'age' => 50,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageTravelAgencies::class)
        ->assertSee('Directorio de agencias de viajes')
        ->assertSee('Nombre')
        ->assertSee('Representante')
        ->assertSee('A DONDE ALIRIO ONLINE')
        ->assertSee('ALIRIO MEDINA')
        ->assertSee('Jurídica')
        ->assertSee('296868581')
        ->assertSee('productos@adondealirio.com');
});

it('renders the travel agency infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 1,
                    'fechaIngreso' => '06/06/2025',
                    'representante' => 'alirio medina',
                    'FechaNacimientoRepresentante' => '27/10/1975',
                    'name' => 'a donde aliRio online',
                    'typeIdentification' => 'J',
                    'numberIdentification' => '296868581',
                    'phone' => '0424 2759838',
                    'phoneAdditional' => '04141336192',
                    'email' => 'productos@adondealirio.com',
                    'userInstagram' => '@adondealirio.online',
                    'age' => 50,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewTravelAgency::class, ['record' => '1'])
        ->assertSee('A DONDE ALIRIO ONLINE')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('ALIRIO MEDINA')
        ->assertSee('296868581')
        ->assertSee('productos@adondealirio.com');
});

it('requires authentication to access the travel agencies list', function () {
    $this->get(route('filament.marketing.resources.agencias-viajes.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
