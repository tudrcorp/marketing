<?php

use App\Filament\Resources\LegalSuppliers\Pages\ManageLegalSuppliers;
use App\Filament\Resources\LegalSuppliers\Pages\ViewLegalSupplier;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the legal suppliers list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/suppliers*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'name' => "\ncentro profesional colonial c.a",
                    'status_convenio' => 'GENERAL',
                    'status_sistema' => 'SIN RESPUESTA DE AFILIACION',
                    'rif' => 'J313713406',
                    'razon_social' => "\ncentro profesional colonial c.a",
                    'personal_phone' => null,
                    'local_phone' => '02468711339',
                    'correo_principal' => 'centroprofesional_nomina@hotmail.com',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageLegalSuppliers::class)
        ->assertSee('Directorio de proveedores jurídicos')
        ->assertSee('CENTRO PROFESIONAL COLONIAL C.A')
        ->assertSee('J313713406')
        ->assertSee('GENERAL')
        ->assertSee('SIN RESPUESTA DE AFILIACION')
        ->assertSee('centroprofesional_nomina@hotmail.com');
});

it('renders the legal supplier infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/suppliers*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'name' => 'centro profesional colonial c.a',
                    'status_convenio' => 'GENERAL',
                    'status_sistema' => 'AFILIADO',
                    'rif' => 'J313713406',
                    'razon_social' => 'centro profesional colonial c.a',
                    'personal_phone' => null,
                    'local_phone' => '02468711339',
                    'correo_principal' => 'centroprofesional_nomina@hotmail.com',
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewLegalSupplier::class, ['record' => 'J313713406'])
        ->assertSee('CENTRO PROFESIONAL COLONIAL C.A')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('J313713406')
        ->assertSee('AFILIADO')
        ->assertSee('centroprofesional_nomina@hotmail.com');
});

it('requires authentication to access the legal suppliers list', function () {
    $this->get(route('filament.marketing.resources.proveedores-juridicos.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
