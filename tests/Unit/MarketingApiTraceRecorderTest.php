<?php

use App\Services\Marketing\MarketingApiTraceRecorder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('api trace recorder structures successful response', function () {
    Http::fake([
        'http://api.test/send' => Http::response(['success' => true, 'id' => 'msg-1'], 200),
    ]);

    $response = Http::post('http://api.test/send', [
        'phone' => '584120000000',
        'copy' => str_repeat('a', 400),
    ]);

    $trace = app(MarketingApiTraceRecorder::class)->fromResponse(
        label: 'Mensajería test',
        endpoint: 'http://api.test/send',
        method: 'POST',
        request: ['phone' => '584120000000', 'copy' => str_repeat('a', 400)],
        response: $response,
    );

    expect($trace['api_calls'])->toHaveCount(1)
        ->and($trace['api_calls'][0]['label'])->toBe('Mensajería test')
        ->and($trace['api_calls'][0]['response']['status'])->toBe(200)
        ->and($trace['api_calls'][0]['response']['body'])->toMatchArray(['success' => true])
        ->and($trace['api_calls'][0]['request']['copy_length'])->toBe(400)
        ->and($trace['api_calls'][0]['request'])->not->toHaveKey('copy');
});

test('api trace recorder structures connection errors', function () {
    $trace = app(MarketingApiTraceRecorder::class)->fromConnectionError(
        label: 'Correo masivo',
        endpoint: 'http://api.test/emails',
        method: 'POST',
        request: ['recipients' => ['a@example.com']],
        exception: new ConnectionException('Connection refused'),
    );

    expect($trace['api_calls'][0]['error']['type'])->toBe('connection')
        ->and($trace['api_calls'][0]['response'])->toBeNull();
});

test('api trace recorder distinguishes a timeout from a real connectivity failure', function () {
    $recorder = app(MarketingApiTraceRecorder::class);

    $timeout = new ConnectionException(
        'cURL error 28: Operation timed out after 120002 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://localhost:4000/api/emails/bulk',
    );
    $unreachable = new ConnectionException('Connection refused');

    expect($recorder->isTimeout($timeout))->toBeTrue()
        ->and($recorder->isTimeout($unreachable))->toBeFalse();
});
