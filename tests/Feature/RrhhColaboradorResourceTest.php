<?php

use App\Filament\Resources\RrhhColaboradores\Pages\ManageRrhhColaboradores;
use App\Filament\Resources\RrhhColaboradores\Pages\ViewRrhhColaborador;
use App\Models\User;
use Tests\Concerns\SafeRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(SafeRefreshDatabase::class);

it('renders the colaboradores list with data from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 5,
                    'fullName' => 'andreina del c, reyes rodriguez',
                    'sexo' => 'Femenino',
                    'fechaNacimiento' => '13/12/1982',
                    'fechaIngreso' => '09/03/2023',
                    'telefono' => '34543534545',
                    'telefonoCorporativo' => '04127018390',
                    'emailCorporativo' => 'areyes@tudrencasa.com',
                    'emailAlternativo' => 'admontudrencasa@gmail.com',
                    'emailPersonal' => 'anreyes313@gmail.com',
                    'age' => 43,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageRrhhColaboradores::class)
        ->assertSee('Directorio de colaboradores')
        ->assertSee('Nombre completo')
        ->assertSee('Fecha de ingreso')
        ->assertSee('ANDREINA DEL C, REYES RODRIGUEZ')
        ->assertSee('Femenino')
        ->assertSee('09/03/2023')
        ->assertSee('43 años')
        ->assertSee('areyes@tudrencasa.com');
});

it('paginates colaboradores across api pages in the filament table', function () {
    Http::fake(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
        $page = (int) ($query['page'] ?? 1);

        if ($page === 1) {
            return Http::response([
                'success' => true,
                'pagination' => ['page' => 1, 'limit' => 10, 'total' => 12, 'totalPages' => 2],
                'data' => collect(range(1, 10))->map(fn (int $id): array => [
                    'id' => $id,
                    'fullName' => sprintf('colaborador %02d', $id),
                    'sexo' => 'Femenino',
                    'emailCorporativo' => "c{$id}@example.com",
                ])->all(),
            ], 200);
        }

        return Http::response([
            'success' => true,
            'pagination' => ['page' => 2, 'limit' => 10, 'total' => 12, 'totalPages' => 2],
            'data' => [
                ['id' => 11, 'fullName' => 'colaborador 11', 'sexo' => 'Masculino', 'emailCorporativo' => 'c11@example.com'],
                ['id' => 12, 'fullName' => 'colaborador 12', 'sexo' => 'Femenino', 'emailCorporativo' => 'c12@example.com'],
            ],
        ], 200);
    });

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ManageRrhhColaboradores::class)
        ->assertSee('COLABORADOR 01')
        ->assertDontSee('COLABORADOR 11')
        ->call('gotoPage', 2, 'page')
        ->assertSee('COLABORADOR 11')
        ->assertSee('COLABORADOR 12')
        ->assertDontSee('COLABORADOR 01');
});

it('renders the colaborador infolist on the view page', function () {
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 5,
                    'fullName' => 'andreina del c, reyes rodriguez',
                    'sexo' => 'Femenino',
                    'fechaNacimiento' => '13/12/1982',
                    'fechaIngreso' => '09/03/2023',
                    'telefono' => '34543534545',
                    'telefonoCorporativo' => '04127018390',
                    'emailCorporativo' => 'areyes@tudrencasa.com',
                    'emailAlternativo' => 'admontudrencasa@gmail.com',
                    'emailPersonal' => 'anreyes313@gmail.com',
                    'age' => 43,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ViewRrhhColaborador::class, ['record' => '5'])
        ->assertSee('ANDREINA DEL C, REYES RODRIGUEZ')
        ->assertSee('General')
        ->assertSee('Contacto')
        ->assertSee('13/12/1982')
        ->assertSee('areyes@tudrencasa.com')
        ->assertSee('anreyes313@gmail.com')
        ->assertSee('04127018390');
});

it('requires authentication to access the colaboradores list', function () {
    $this->get(route('filament.marketing.resources.colaboradores.index'))
        ->assertRedirect(route('filament.marketing.auth.login'));
});
