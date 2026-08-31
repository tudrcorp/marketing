<?php

use App\Services\Marketing\MarketingApiHealthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

it('reports api health as ok when the endpoint responds successfully', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::response(['status' => 'ok'], 200),
    ]);

    $result = app(MarketingApiHealthService::class)->checkApi();

    expect($result->isHealthy)->toBeTrue()
        ->and($result->statusCode)->toBe(200)
        ->and($result->statusText())->toBe('OK')
        ->and($result->endpoint)->toBe('http://localhost:4000/api/health');
});

it('reports database health as ok when the endpoint responds successfully', function () {
    Http::fake([
        'http://localhost:4000/api/health/db' => Http::response(['status' => 'ok'], 200),
    ]);

    $result = app(MarketingApiHealthService::class)->checkDatabase();

    expect($result->isHealthy)->toBeTrue()
        ->and($result->statusCode)->toBe(200)
        ->and($result->endpoint)->toBe('http://localhost:4000/api/health/db');
});

it('reports unhealthy status when the api endpoint fails', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::response(['message' => 'down'], 503),
    ]);

    $result = app(MarketingApiHealthService::class)->checkApi();

    expect($result->isHealthy)->toBeFalse()
        ->and($result->statusCode)->toBe(503)
        ->and($result->statusText())->toBe('ERROR')
        ->and($result->message)->toBe('down');
});

it('reports unhealthy status when the api is unreachable', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection refused');
    });

    $result = app(MarketingApiHealthService::class)->checkDatabase();

    expect($result->isHealthy)->toBeFalse()
        ->and($result->statusText())->toBe('ERROR')
        ->and($result->message)->toBe('No se pudo conectar con el API.');
});
