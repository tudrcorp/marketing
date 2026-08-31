<?php

use App\Services\Marketing\MarketingCorporateAffiliatesApiService;
use Illuminate\Support\Facades\Http;

it('paginates corporate affiliates from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/affiliate-corporates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 949, 'totalPages' => 95],
            'data' => [
                [
                    'id' => 480,
                    'first_name' => ' ALVARADO FERNANDO ANTONIO ',
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

    $paginator = app(MarketingCorporateAffiliatesApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(949)
        ->and($paginator->items()[0]['id'])->toBe('480')
        ->and($paginator->items()[0]['first_name'])->toBe('ALVARADO FERNANDO ANTONIO');
});

it('sorts corporate affiliates alphabetically by name', function () {
    Http::fake([
        'http://localhost:4000/api/affiliate-corporates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'totalPages' => 1],
            'data' => [
                ['id' => 2, 'first_name' => ' Zeta Corporativo '],
                ['id' => 1, 'first_name' => ' Alpha Corporativo '],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingCorporateAffiliatesApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'first_name',
        sortDirection: 'asc',
    );

    expect(collect($paginator->items())->pluck('first_name')->all())
        ->toBe(['Alpha Corporativo', 'Zeta Corporativo']);
});

it('finds a corporate affiliate by id', function () {
    Http::fake([
        'http://localhost:4000/api/affiliate-corporates*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 480, 'first_name' => ' ALVARADO FERNANDO ANTONIO '],
            ],
        ], 200),
    ]);

    expect(app(MarketingCorporateAffiliatesApiService::class)->find('480'))
        ->not->toBeNull()
        ->and(app(MarketingCorporateAffiliatesApiService::class)->find('480')['first_name'])
        ->toBe('ALVARADO FERNANDO ANTONIO');
});
