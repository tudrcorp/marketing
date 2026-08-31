<?php

use App\Services\Marketing\MarketingIndividualAffiliatesApiService;
use Illuminate\Support\Facades\Http;

it('paginates individual affiliates from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/affiliates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 298, 'totalPages' => 30],
            'data' => [
                [
                    'id' => 54,
                    'full_name' => 'ABRAHAN JOSUE GOMEZ LOPEZ',
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

    $paginator = app(MarketingIndividualAffiliatesApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(298)
        ->and($paginator->items()[0]['id'])->toBe('54')
        ->and($paginator->items()[0]['full_name'])->toBe('ABRAHAN JOSUE GOMEZ LOPEZ');
});

it('finds an individual affiliate by id', function () {
    Http::fake([
        'http://localhost:4000/api/affiliates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 54, 'full_name' => 'ABRAHAN JOSUE GOMEZ LOPEZ'],
            ],
        ], 200),
    ]);

    expect(app(MarketingIndividualAffiliatesApiService::class)->find('54'))
        ->not->toBeNull()
        ->and(app(MarketingIndividualAffiliatesApiService::class)->find('54')['full_name'])
        ->toBe('ABRAHAN JOSUE GOMEZ LOPEZ');
});
