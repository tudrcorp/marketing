<?php

use App\Services\Marketing\MarketingAgenciesApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('paginates broker agencies from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::response([
            'success' => true,
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 54,
                'totalPages' => 6,
            ],
            'data' => [
                [
                    'id' => 26,
                    'code' => 'TDG-126',
                    'agency_type_id' => '3',
                    'rif' => null,
                    'name_corporative' => 'A & J Internacional Multiservice',
                    'ci_responsable' => null,
                    'email' => 'navasaura11@gmail.com',
                    'phone' => '4141019345',
                    'user_instagram' => null,
                    'name_contact_2' => null,
                    'email_contact_2' => null,
                    'phone_contact_2' => null,
                ],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingAgenciesApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(54)
        ->and($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]['id'])->toBe('26')
        ->and($paginator->items()[0]['code'])->toBe('TDG-126')
        ->and($paginator->items()[0]['name_corporative'])->toBe('A & J Internacional Multiservice');
});

it('sorts broker agencies alphabetically by name', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'totalPages' => 1],
            'data' => [
                ['id' => 2, 'name_corporative' => 'Zeta Agencia'],
                ['id' => 1, 'name_corporative' => 'Alpha Agencia'],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingAgenciesApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'name_corporative',
        sortDirection: 'asc',
    );

    expect(collect($paginator->items())->pluck('name_corporative')->all())
        ->toBe(['Alpha Agencia', 'Zeta Agencia']);
});

it('passes pagination and search parameters to the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 2, 'limit' => 25, 'total' => 0, 'totalPages' => 0],
            'data' => [],
        ], 200),
    ]);

    app(MarketingAgenciesApiService::class)->paginate(page: 2, perPage: 25, search: 'ABP');

    Http::assertSent(function ($request) {
        $url = parse_url($request->url());
        parse_str($url['query'] ?? '', $query);

        return ($url['host'] ?? '') === 'localhost'
            && ($url['path'] ?? '') === '/api/agencies'
            && (int) ($query['page'] ?? 0) === 2
            && (int) ($query['limit'] ?? 0) === 25
            && ($query['search'] ?? '') === 'ABP';
    });
});

it('finds a broker agency by id across paginated api results', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::sequence()
            ->push([
                'success' => true,
                'pagination' => ['page' => 1, 'limit' => 10, 'total' => 11, 'totalPages' => 2],
                'data' => [
                    ['id' => 1, 'name_corporative' => 'Primera'],
                ],
            ], 200)
            ->push([
                'success' => true,
                'pagination' => ['page' => 2, 'limit' => 10, 'total' => 11, 'totalPages' => 2],
                'data' => [
                    ['id' => 26, 'code' => 'TDG-126', 'name_corporative' => 'A & J Internacional Multiservice'],
                ],
            ], 200),
    ]);

    $agency = app(MarketingAgenciesApiService::class)->find('26');

    expect($agency)->not->toBeNull()
        ->and($agency['id'])->toBe('26')
        ->and($agency['code'])->toBe('TDG-126');
});

it('returns an empty paginator when the marketing api fails', function () {
    Http::fake([
        'http://localhost:4000/api/agencies*' => Http::response(['message' => 'down'], 503),
    ]);

    $paginator = app(MarketingAgenciesApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(0)
        ->and($paginator->items())->toBeEmpty();
});

it('returns an empty paginator when the marketing api is unreachable', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $paginator = app(MarketingAgenciesApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(0)
        ->and($paginator->items())->toBeEmpty();
});
