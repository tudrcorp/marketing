<?php

use App\Services\Marketing\MarketingRrhhColaboradoresApiService;
use Illuminate\Support\Facades\Http;

it('paginates rrhh colaboradores from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 5,
                    'fullName' => 'ANDREINA DEL C, REYES RODRIGUEZ',
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

    $paginator = app(MarketingRrhhColaboradoresApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(1)
        ->and($paginator->perPage())->toBe(10)
        ->and($paginator->items()[0]['id'])->toBe('5')
        ->and($paginator->items()[0]['fullName'])->toBe('ANDREINA DEL C, REYES RODRIGUEZ');
});

it('aggregates api pages so filament page sizes above 10 still work', function () {
    Http::fake(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
        $page = (int) ($query['page'] ?? 1);

        if ($page === 1) {
            return Http::response([
                'success' => true,
                'pagination' => ['page' => 1, 'limit' => 10, 'total' => 12, 'totalPages' => 2],
                'data' => collect(range(1, 10))->map(fn (int $id): array => [
                    'id' => $id,
                    'fullName' => sprintf('Colaborador %02d', $id),
                    'emailCorporativo' => "c{$id}@example.com",
                ])->all(),
            ], 200);
        }

        return Http::response([
            'success' => true,
            'pagination' => ['page' => 2, 'limit' => 10, 'total' => 12, 'totalPages' => 2],
            'data' => [
                ['id' => 11, 'fullName' => 'Colaborador 11', 'emailCorporativo' => 'c11@example.com'],
                ['id' => 12, 'fullName' => 'Colaborador 12', 'emailCorporativo' => 'c12@example.com'],
            ],
        ], 200);
    });

    $pageOne = app(MarketingRrhhColaboradoresApiService::class)->paginate(page: 1, perPage: 25);

    expect($pageOne->total())->toBe(12)
        ->and($pageOne->perPage())->toBe(25)
        ->and($pageOne->lastPage())->toBe(1)
        ->and($pageOne->items())->toHaveCount(12)
        ->and(collect($pageOne->items())->pluck('id')->all())->toBe([
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12',
        ]);
});

it('paginates locally across aggregated api pages', function () {
    Http::fake(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);
        $page = (int) ($query['page'] ?? 1);

        if ($page === 1) {
            return Http::response([
                'success' => true,
                'pagination' => ['page' => 1, 'limit' => 10, 'total' => 12, 'totalPages' => 2],
                'data' => collect(range(1, 10))->map(fn (int $id): array => [
                    'id' => $id,
                    'fullName' => sprintf('Colaborador %02d', $id),
                ])->all(),
            ], 200);
        }

        return Http::response([
            'success' => true,
            'pagination' => ['page' => 2, 'limit' => 10, 'total' => 12, 'totalPages' => 2],
            'data' => [
                ['id' => 11, 'fullName' => 'Colaborador 11'],
                ['id' => 12, 'fullName' => 'Colaborador 12'],
            ],
        ], 200);
    });

    $pageTwo = app(MarketingRrhhColaboradoresApiService::class)->paginate(page: 2, perPage: 10);

    expect($pageTwo->total())->toBe(12)
        ->and($pageTwo->currentPage())->toBe(2)
        ->and(collect($pageTwo->items())->pluck('id')->all())->toBe(['11', '12']);
});

it('searches colaboradores locally by name email or phone', function () {
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 1,
                    'fullName' => 'Alpha Colaborador',
                    'emailCorporativo' => 'alpha@example.com',
                    'telefonoCorporativo' => '04141234567',
                ],
                [
                    'id' => 2,
                    'fullName' => 'Zeta Colaborador',
                    'emailCorporativo' => 'zeta@example.com',
                    'telefonoCorporativo' => '04149876543',
                ],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingRrhhColaboradoresApiService::class)->paginate(
        page: 1,
        perPage: 10,
        search: 'zeta@',
    );

    expect($paginator->total())->toBe(1)
        ->and($paginator->items()[0]['fullName'])->toBe('Zeta Colaborador');
});

it('sorts colaboradores alphabetically by full name', function () {
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'totalPages' => 1],
            'data' => [
                ['id' => 2, 'fullName' => 'Zeta Colaborador'],
                ['id' => 1, 'fullName' => 'Alpha Colaborador'],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingRrhhColaboradoresApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'fullName',
        sortDirection: 'asc',
    );

    expect(collect($paginator->items())->pluck('fullName')->all())
        ->toBe(['Alpha Colaborador', 'Zeta Colaborador']);
});

it('finds a colaborador by id', function () {
    Http::fake([
        'http://localhost:4000/api/rrhh-colaboradores*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 5, 'fullName' => 'ANDREINA DEL C, REYES RODRIGUEZ'],
            ],
        ], 200),
    ]);

    expect(app(MarketingRrhhColaboradoresApiService::class)->find('5'))
        ->not->toBeNull()
        ->and(app(MarketingRrhhColaboradoresApiService::class)->find('5')['fullName'])
        ->toBe('ANDREINA DEL C, REYES RODRIGUEZ');
});
