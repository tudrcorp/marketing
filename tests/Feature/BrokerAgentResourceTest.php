<?php

use App\Filament\Resources\BrokerAgents\Pages\ManageBrokerAgents;
use App\Filament\Resources\BrokerAgents\Pages\ViewBrokerAgent;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the broker agents list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 1,
                'totalPages' => 1,
            ],
            'data' => [
                [
                    'id' => 367,
                    'code' => null,
                    'agency_type_id' => '2',
                    'rif' => null,
                    'name_corporative' => 'Adaina del Valle Gutierrez S',
                    'ci_responsable' => 'V-12345678',
                    'birth_date' => '1981-01-18',
                    'email' => 'adaina@example.com',
                    'phone' => '04141234567',
                    'user_instagram' => null,
                    'name_contact_2' => null,
                    'email_contact_2' => null,
                    'phone_contact_2' => null,
                    'age' => 45,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ManageBrokerAgents::class)
        ->assertSee('Directorio de agentes')
        ->assertSee('Nombre corporativo')
        ->assertSee('CI responsable')
        ->assertSee('Fecha de nacimiento')
        ->assertSee('18/01/1981')
        ->assertSee('Correo')
        ->assertSee('Teléfono')
        ->assertSee('Tipo de agencia')
        ->assertSee('ADAINA DEL VALLE GUTIERREZ S')
        ->assertDontSee('Adaina del Valle Gutierrez S')
        ->assertSee('adaina@example.com')
        ->assertSee('Corredor')
        ->assertSee('367');
});

it('renders the broker agent infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 1,
                'totalPages' => 1,
            ],
            'data' => [
                [
                    'id' => 367,
                    'code' => 'AG-001',
                    'agency_type_id' => '2',
                    'rif' => 'J-12345678-9',
                    'name_corporative' => 'Adaina del Valle Gutierrez S',
                    'ci_responsable' => 'V-12345678',
                    'birth_date' => '1981-01-18',
                    'email' => 'adaina@example.com',
                    'phone' => '04141234567',
                    'user_instagram' => '@adaina',
                    'name_contact_2' => 'Contacto Secundario',
                    'email_contact_2' => 'secundario@example.com',
                    'phone_contact_2' => '04140000000',
                    'age' => 45,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ViewBrokerAgent::class, ['record' => '367'])
        ->assertSee('Adaina del Valle Gutierrez S')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('Contacto alternativo')
        ->assertSee('Identificación')
        ->assertSee('Fecha de nacimiento')
        ->assertSee('18/01/1981')
        ->assertSee('adaina@example.com')
        ->assertSee('V-12345678')
        ->assertSee('Contacto Secundario');
});

it('requires authentication to access the broker agents list', function () {
    $this->get(route('filament.marketing.resources.agentes-corretaje.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
