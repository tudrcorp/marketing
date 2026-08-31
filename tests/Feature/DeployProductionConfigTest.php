<?php

test('production env example uses mysql and redis instead of sqlite or file stores', function () {
    $env = file_get_contents(base_path('deploy/env.production.example'));

    expect($env)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('DB_CONNECTION=mysql')
        ->toContain('CACHE_STORE=redis')
        ->toContain('SESSION_DRIVER=redis')
        ->toContain('SESSION_CONNECTION=session')
        ->toContain('QUEUE_CONNECTION=redis')
        ->toContain('REDIS_QUEUE_RETRY_AFTER=960')
        ->toContain('REDIS_PERSISTENT=true')
        ->not->toContain('DB_CONNECTION=sqlite');
});

test('redis exposes an isolated session connection', function () {
    expect(config('database.redis'))->toHaveKey('session')
        ->and((string) config('database.redis.session.database'))->not->toBe((string) config('database.redis.default.database'))
        ->and((string) config('database.redis.session.database'))->not->toBe((string) config('database.redis.cache.database'));
});
