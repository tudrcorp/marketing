<?php

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('simulate dispatch command calls bulk api with dry run without sending emails', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 2, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 1,
                    'name_corporative' => 'Agente Uno',
                    'birth_date' => '1981-01-18',
                    'email' => 'uno@example.com',
                ],
                [
                    'id' => 2,
                    'name_corporative' => 'Agente Dos',
                    'birth_date' => '1981-01-18',
                    'email' => 'dos@example.com',
                ],
            ],
        ], 200),
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'dry_run' => true,
            'message' => 'Simulación realizada sin enviar correos',
            'sent' => 2,
            'total' => 2,
        ], 200),
    ]);

    $notification = BirthdayNotification::factory()->create([
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::BrokerAgents->value],
    ]);

    $this->artisan('birthday:simulate-dispatch', [
        'notification' => $notification->getKey(),
        '--date' => '2026-01-18',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Simulación exitosa: 2 correos validados por el API sin envío real.');

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && str_contains($body, 'dry_run')
            && str_contains($body, 'true')
            && str_contains($body, '<html');
    });
});

test('bulk email service forwards dry run flag to marketing api', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'dry_run' => true,
            'message' => 'Simulación realizada sin enviar correos',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $notification = BirthdayNotification::factory()->create([
        'copy' => '¡Feliz cumpleaños!',
        'channels' => [BirthdayNotificationChannel::Email->value],
    ]);

    $result = app(\App\Services\Marketing\BirthdayNotificationBulkEmailService::class)->sendBatch(
        notification: $notification,
        recipients: ['sim@example.com'],
        dryRun: true,
    );

    expect($result->successful)->toBeTrue()
        ->and($result->dryRun)->toBeTrue()
        ->and($result->sent)->toBe(1);

    Http::assertSent(fn ($request): bool => str_contains($request->body(), 'dry_run')
        && str_contains($request->body(), 'true'));
});
