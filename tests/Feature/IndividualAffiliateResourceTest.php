<?php

use App\Filament\Resources\IndividualAffiliates\Pages\ManageIndividualAffiliates;
use App\Filament\Resources\IndividualAffiliates\Pages\ViewIndividualAffiliate;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the individual affiliates list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/affiliates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 54,
                    'full_name' => 'Abrahan Josue Gomez Lopez',
                    'nro_identificacion' => '33428064',
                    'birth_date' => '01/07/2010',
                    'sex' => 'MASCULINO',
                    'phone' => '4143491540',
                    'email' => 'mabel_lopez@gmail.com',
                    'age' => 15,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageIndividualAffiliates::class)
        ->assertSee('Directorio de afiliados individuales')
        ->assertSee('Nombre completo')
        ->assertSee('Identificación')
        ->assertSee('ABRAHAN JOSUE GOMEZ LOPEZ')
        ->assertSee('Masculino')
        ->assertSee('mabel_lopez@gmail.com');
});

it('renders the individual affiliate infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/affiliates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 54,
                    'full_name' => 'Abrahan Josue Gomez Lopez',
                    'nro_identificacion' => '33428064',
                    'birth_date' => '01/07/2010',
                    'sex' => 'MASCULINO',
                    'phone' => '4143491540',
                    'email' => 'mabel_lopez@gmail.com',
                    'age' => 15,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewIndividualAffiliate::class, ['record' => '54'])
        ->assertSee('Abrahan Josue Gomez Lopez')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('33428064')
        ->assertSee('mabel_lopez@gmail.com');
});

it('requires authentication to access the individual affiliates list', function () {
    $this->get(route('filament.marketing.resources.afiliados-individuales.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
