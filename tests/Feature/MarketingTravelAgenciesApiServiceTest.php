<?php

use App\Services\Marketing\MarketingTravelAgenciesApiService;
use Illuminate\Support\Facades\Http;

it('paginates travel agencies from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 170, 'totalPages' => 17],
            'data' => [
                [
                    'id' => 1,
                    'fechaIngreso' => '06/06/2025',
                    'representante' => 'ALIRIO MEDINA',
                    'FechaNacimientoRepresentante' => '27/10/1975',
                    'name' => 'A Donde Alirio Online',
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

    $paginator = app(MarketingTravelAgenciesApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(170)
        ->and($paginator->items()[0]['id'])->toBe('1')
        ->and($paginator->items()[0]['name'])->toBe('A Donde Alirio Online');
});

it('sorts travel agencies alphabetically by name', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'totalPages' => 1],
            'data' => [
                ['id' => 2, 'name' => 'Zeta Viajes'],
                ['id' => 1, 'name' => 'Alpha Viajes'],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingTravelAgenciesApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'name',
        sortDirection: 'asc',
    );

    expect(collect($paginator->items())->pluck('name')->all())
        ->toBe(['Alpha Viajes', 'Zeta Viajes']);
});

it('finds a travel agency by id', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 1, 'name' => 'A Donde Alirio Online'],
            ],
        ], 200),
    ]);

    expect(app(MarketingTravelAgenciesApiService::class)->find('1'))
        ->not->toBeNull()
        ->and(app(MarketingTravelAgenciesApiService::class)->find('1')['name'])
        ->toBe('A Donde Alirio Online');
});
