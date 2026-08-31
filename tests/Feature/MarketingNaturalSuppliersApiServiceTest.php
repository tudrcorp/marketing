<?php

use App\Services\Marketing\MarketingNaturalSuppliersApiService;
use Illuminate\Support\Facades\Http;

it('paginates natural suppliers from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/doctor-nurses*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 168, 'totalPages' => 17],
            'data' => [
                [
                    'id' => 146,
                    'name' => 'ADRIANA  PEREZ',
                    'rif' => '',
                    'razon_social' => 'ADRIANA  PEREZ',
                    'personal_phone' => '4246892744',
                    'local_phone' => '',
                    'correo_principal' => 'adrianaperezlamoine@hotmail.com',
                ],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingNaturalSuppliersApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(168)
        ->and($paginator->items()[0]['id'])->toBe('146')
        ->and($paginator->items()[0]['name'])->toBe('ADRIANA  PEREZ');
});

it('finds a natural supplier by id', function () {
    Http::fake([
        'http://localhost:4000/api/doctor-nurses*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 146, 'name' => 'ADRIANA  PEREZ'],
            ],
        ], 200),
    ]);

    expect(app(MarketingNaturalSuppliersApiService::class)->find('146'))
        ->not->toBeNull()
        ->and(app(MarketingNaturalSuppliersApiService::class)->find('146')['name'])
        ->toBe('ADRIANA  PEREZ');
});
