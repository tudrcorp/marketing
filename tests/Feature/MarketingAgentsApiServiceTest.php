<?php

use App\Services\Marketing\MarketingAgentsApiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('paginates broker agents from the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 388,
                'totalPages' => 39,
            ],
            'data' => [
                [
                    'id' => 367,
                    'code' => null,
                    'agency_type_id' => '2',
                    'rif' => null,
                    'name_corporative' => 'ADAINA DEL VALLE GUTIERREZ S',
                    'ci_responsable' => 'V-12345678',
                    'email' => 'adaina@example.com',
                    'phone' => '04141234567',
                    'user_instagram' => null,
                    'name_contact_2' => null,
                    'email_contact_2' => null,
                    'phone_contact_2' => null,
                    'age' => 45,
                ],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingAgentsApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(388)
        ->and($paginator->perPage())->toBe(10)
        ->and($paginator->currentPage())->toBe(1)
        ->and($paginator->items())->toHaveCount(1)
        ->and($paginator->items()[0]['id'])->toBe('367')
        ->and($paginator->items()[0]['name_corporative'])->toBe('ADAINA DEL VALLE GUTIERREZ S');
});

it('sorts broker agents alphabetically by name', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 3, 'totalPages' => 1],
            'data' => [
                ['id' => 3, 'name_corporative' => 'Zeta Agente'],
                ['id' => 1, 'name_corporative' => 'Alpha Agente'],
                ['id' => 2, 'name_corporative' => 'beta agente'],
            ],
        ], 200),
    ]);

    $paginator = app(MarketingAgentsApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'name_corporative',
        sortDirection: 'asc',
    );

    expect(collect($paginator->items())->pluck('name_corporative')->all())
        ->toBe(['Alpha Agente', 'beta agente', 'Zeta Agente']);
});

it('passes sort parameters to the marketing api', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'totalPages' => 0],
            'data' => [],
        ], 200),
    ]);

    app(MarketingAgentsApiService::class)->paginate(
        page: 1,
        perPage: 10,
        sortColumn: 'name_corporative',
        sortDirection: 'desc',
    );

    Http::assertSent(function ($request) {
        $url = parse_url($request->url());
        parse_str($url['query'] ?? '', $query);

        return ($query['sort'] ?? '') === 'name_corporative'
            && ($query['order'] ?? '') === 'desc';
    });
});

it('passes pagination and search parameters to the marketing api', function () {
    config()->set('services.marketing_api.key', 'test-marketing-api-key');

    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 2, 'limit' => 25, 'total' => 0, 'totalPages' => 0],
            'data' => [],
        ], 200),
    ]);

    app(MarketingAgentsApiService::class)->paginate(page: 2, perPage: 25, search: 'Adrian');

    Http::assertSent(function ($request) {
        $url = parse_url($request->url());
        parse_str($url['query'] ?? '', $query);

        return ($url['host'] ?? '') === 'localhost'
            && ($url['path'] ?? '') === '/api/agents'
            && (int) ($query['page'] ?? 0) === 2
            && (int) ($query['limit'] ?? 0) === 25
            && ($query['search'] ?? '') === 'Adrian'
            && $request->hasHeader('X-API-Key', 'test-marketing-api-key');
    });
});

it('returns an empty paginator when the marketing api fails', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response(['message' => 'down'], 503),
    ]);

    $paginator = app(MarketingAgentsApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(0)
        ->and($paginator->items())->toBeEmpty();
});

it('finds a broker agent by id across paginated api results', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::sequence()
            ->push([
                'success' => true,
                'pagination' => ['page' => 1, 'limit' => 10, 'total' => 11, 'totalPages' => 2],
                'data' => [
                    ['id' => 1, 'name_corporative' => 'Primero'],
                ],
            ], 200)
            ->push([
                'success' => true,
                'pagination' => ['page' => 2, 'limit' => 10, 'total' => 11, 'totalPages' => 2],
                'data' => [
                    ['id' => 367, 'name_corporative' => 'ADAINA DEL VALLE GUTIERREZ S'],
                ],
            ], 200),
    ]);

    $agent = app(MarketingAgentsApiService::class)->find('367');

    expect($agent)->not->toBeNull()
        ->and($agent['id'])->toBe('367')
        ->and($agent['name_corporative'])->toBe('ADAINA DEL VALLE GUTIERREZ S');
});

it('returns null when the broker agent is not found', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 1, 'totalPages' => 1],
            'data' => [
                ['id' => 1, 'name_corporative' => 'Primero'],
            ],
        ], 200),
    ]);

    expect(app(MarketingAgentsApiService::class)->find('999'))->toBeNull();
});

it('returns an empty paginator when the marketing api is unreachable', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $paginator = app(MarketingAgentsApiService::class)->paginate(page: 1, perPage: 10);

    expect($paginator->total())->toBe(0)
        ->and($paginator->items())->toBeEmpty();
});
