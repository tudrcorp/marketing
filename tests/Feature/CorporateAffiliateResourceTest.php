<?php

use App\Filament\Resources\CorporateAffiliates\Pages\ManageCorporateAffiliates;
use App\Filament\Resources\CorporateAffiliates\Pages\ViewCorporateAffiliate;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the corporate affiliates list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/affiliate-corporates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 480,
                    'first_name' => ' alvarado fernando antonio ',
                    'nro_identificacion' => '14437369',
                    'birth_date' => '25/08/1980',
                    'sex' => 'MASCULINO',
                    'phone' => '4143584743',
                    'email' => '',
                    'age' => 45,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageCorporateAffiliates::class)
        ->assertSee('Directorio de afiliados corporativos')
        ->assertSee('Nombre')
        ->assertSee('Identificación')
        ->assertSee('ALVARADO FERNANDO ANTONIO')
        ->assertSee('Masculino');
});

it('renders the corporate affiliate infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/affiliate-corporates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 480,
                    'first_name' => ' alvarado fernando antonio ',
                    'nro_identificacion' => '14437369',
                    'birth_date' => '25/08/1980',
                    'sex' => 'MASCULINO',
                    'phone' => '4143584743',
                    'email' => 'corporativo@example.com',
                    'age' => 45,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewCorporateAffiliate::class, ['record' => '480'])
        ->assertSee('alvarado fernando antonio')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('14437369')
        ->assertSee('corporativo@example.com');
});

it('requires authentication to access the corporate affiliates list', function () {
    $this->get(route('filament.marketing.resources.afiliados-corporativos.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
