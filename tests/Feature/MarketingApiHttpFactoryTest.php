<?php

use App\Services\Marketing\MarketingApiHttpFactory;
use Illuminate\Support\Facades\Http;

it('sends the marketing api key header when configured', function () {
    config()->set('services.marketing_api.key', 'test-marketing-api-key');

    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'totalPages' => 0],
            'data' => [],
        ], 200),
    ]);

    app(MarketingApiHttpFactory::class)
        ->client()
        ->get('http://localhost:4000/api/agents');

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-API-Key', 'test-marketing-api-key');
    });
});

it('omits the marketing api key header when it is not configured', function () {
    config()->set('services.marketing_api.key', null);

    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'totalPages' => 0],
            'data' => [],
        ], 200),
    ]);

    app(MarketingApiHttpFactory::class)
        ->client()
        ->get('http://localhost:4000/api/agents');

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-API-Key');
    });
});

it('uses a longer timeout for email api clients', function () {
    config()->set('services.marketing_api.timeout', 15);
    config()->set('services.marketing_api.email_timeout', 120);

    $client = app(MarketingApiHttpFactory::class)->client();
    $emailClient = app(MarketingApiHttpFactory::class)->emailClient();

    expect(invade($client)->options['timeout'] ?? null)->toBe(15)
        ->and(invade($emailClient)->options['timeout'] ?? null)->toBe(120);
});
