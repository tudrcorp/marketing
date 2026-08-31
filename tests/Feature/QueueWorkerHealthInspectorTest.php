<?php

use App\Services\Marketing\QueueWorkerHealthInspector;

beforeEach(function () {
    // La conexión `sync` no necesita worker, así que se simula una asíncrona real.
    config()->set('queue.default', 'database');
});

test('warns when no worker has reported a heartbeat', function () {
    $message = app(QueueWorkerHealthInspector::class)->unavailableMessage(['default', 'email']);

    expect($message)->toBeString()
        ->and($message)->toContain('No hay ningún worker')
        ->and($message)->toContain('email')
        ->and($message)->toContain('queue:work');
});

test('stays silent while every required queue has a fresh heartbeat', function () {
    $inspector = app(QueueWorkerHealthInspector::class);
    $inspector->recordHeartbeat('default');
    $inspector->recordHeartbeat('email');

    expect($inspector->unavailableMessage(['default', 'email']))->toBeNull();
});

test('warns when only one of the required queues has a worker', function () {
    $inspector = app(QueueWorkerHealthInspector::class);
    $inspector->recordHeartbeat('default');

    $message = $inspector->unavailableMessage(['default', 'email']);

    expect($message)->toBeString()
        ->and($message)->toContain('email')
        ->and($message)->not->toContain('«default»');
});

test('treats a stale heartbeat as a dead worker', function () {
    $inspector = app(QueueWorkerHealthInspector::class);
    $inspector->recordHeartbeat('email');

    $this->travel(10)->minutes();

    expect($inspector->isWorkerRunning('email'))->toBeFalse();
});

test('never blocks the sync connection because jobs run inline', function () {
    config()->set('queue.default', 'sync');

    expect(app(QueueWorkerHealthInspector::class)->unavailableMessage(['default', 'email']))->toBeNull();
});
