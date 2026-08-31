<?php

use App\Filament\Resources\TravelAgents\Pages\ManageTravelAgents;
use App\Filament\Resources\TravelAgents\Pages\ViewTravelAgent;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the travel agents list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 29,
                    'name' => 'ailyn viña',
                    'email' => 'ailynvina@gmail.com',
                    'phone' => '04122067511',
                    'fechaNacimiento' => '1988-04-23',
                    'age' => 38,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageTravelAgents::class)
        ->assertSee('Directorio de agentes de viajes')
        ->assertSee('Nombre')
        ->assertSee('Fecha de nacimiento')
        ->assertSee('AILYN VIÑA')
        ->assertSee('23/04/1988')
        ->assertSee('38 años')
        ->assertSee('ailynvina@gmail.com');
});

it('renders the travel agent infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 29,
                    'name' => 'ailyn viña',
                    'email' => 'ailynvina@gmail.com',
                    'phone' => '04122067511',
                    'fechaNacimiento' => '1988-04-23',
                    'age' => 38,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewTravelAgent::class, ['record' => '29'])
        ->assertSee('AILYN VIÑA')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('23/04/1988')
        ->assertSee('ailynvina@gmail.com')
        ->assertSee('04122067511');
});

it('requires authentication to access the travel agents list', function () {
    $this->get(route('filament.marketing.resources.agentes-viajes.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
