<?php

use App\Services\Marketing\MarketingTravelAgentsApiService;
use Illuminate\Support\Facades\Http;

it('paginates travel agents from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 47, 'totalPages' => 5],
            'data' => [
                [
                    'id' => 29,
                    'name' => 'AILYN VIÑA ',
                    'email' => 'AILYNVINA@GMAIL.COM',
                    'phone' => '04122067511',
                    'fechaNacimiento' => '1988-04-23',
                    'age' => 38,
                ],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingTravelAgentsApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(47)
        ->and($paginator->items()[0]['id'])->toBe('29')
        ->and($paginator->items()[0]['name'])->toBe('AILYN VIÑA');
});

it('sorts travel agents alphabetically by name', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'totalPages' => 1],
            'data' => [
                ['id' => 2, 'name' => ' Zeta Agente '],
                ['id' => 1, 'name' => ' Alpha Agente '],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingTravelAgentsApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'name',
        sortDirection: 'asc',
    );

    expect(collect($paginator->items())->pluck('name')->all())
        ->toBe(['Alpha Agente', 'Zeta Agente']);
});

it('finds a travel agent by id', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 29, 'name' => 'AILYN VIÑA '],
            ],
        ], 200),
    ]);

    expect(app(MarketingTravelAgentsApiService::class)->find('29'))
        ->not->toBeNull()
        ->and(app(MarketingTravelAgentsApiService::class)->find('29')['name'])
        ->toBe('AILYN VIÑA');
});
