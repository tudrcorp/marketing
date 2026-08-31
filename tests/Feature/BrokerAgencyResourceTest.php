<?php

use App\Filament\Resources\BrokerAgencies\Pages\ManageBrokerAgencies;
use App\Filament\Resources\BrokerAgencies\Pages\ViewBrokerAgency;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the broker agencies list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::response([
            'success' => true,
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 1,
                'totalPages' => 1,
            ],
            'data' => [
                [
                    'id' => 26,
                    'code' => 'TDG-126',
                    'agency_type_id' => '3',
                    'rif' => null,
                    'name_corporative' => 'a & j internacional multiservice',
                    'ci_responsable' => null,
                    'email' => 'navasaura11@gmail.com',
                    'phone' => '4141019345',
                    'user_instagram' => null,
                    'name_contact_2' => null,
                    'email_contact_2' => null,
                    'phone_contact_2' => null,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ManageBrokerAgencies::class)
        ->assertSee('Directorio de agencias')
        ->assertSee('Código TDG')
        ->assertSee('Nombre corporativo')
        ->assertSee('A & J INTERNACIONAL MULTISERVICE')
        ->assertSee('TDG-126')
        ->assertSee('GENERAL')
        ->assertSee('navasaura11@gmail.com');
});

it('renders the broker agency infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::response([
            'success' => true,
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 1,
                'totalPages' => 1,
            ],
            'data' => [
                [
                    'id' => 26,
                    'code' => 'TDG-126',
                    'agency_type_id' => '3',
                    'rif' => 'J-12345678-9',
                    'name_corporative' => 'a & j internacional multiservice',
                    'ci_responsable' => 'V-12345678',
                    'email' => 'navasaura11@gmail.com',
                    'phone' => '4141019345',
                    'user_instagram' => '@agencia',
                    'name_contact_2' => 'Contacto Secundario',
                    'email_contact_2' => 'secundario@example.com',
                    'phone_contact_2' => '04140000000',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ViewBrokerAgency::class, ['record' => '26'])
        ->assertSee('a & j internacional multiservice')
        ->assertSee('TDG-126')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('Contacto alternativo')
        ->assertSee('navasaura11@gmail.com')
        ->assertSee('Contacto Secundario');
});

it('requires authentication to access the broker agencies list', function () {
    $this->get(route('filament.marketing.resources.agencias-corretaje.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
