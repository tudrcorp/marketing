<?php

use App\Jobs\ProcessBirthdayNotificationJob;
use App\Jobs\SendBirthdayEmailBatchJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use App\Services\Marketing\NotificationDispatchLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('process birthday notification job counts recipients and dispatches batches of fifty', function () {
    Queue::fake();

    Http::fake([
        'http://localhost:4000/api/agents*' => function ($request) {
            $page = (int) $request->data()['page'];
            $records = [];

            for ($index = 0; $index < 100; $index++) {
                $id = (($page - 1) * 100) + $index + 1;

                if ($id > 120) {
                    break;
                }

                $records[] = [
                    'id' => $id,
                    'name_corporative' => "Agente {$id}",
                    'birth_date' => '1981-01-18',
                    'email' => "agente{$id}@example.com",
                ];
            }

            return Http::response([
                'success' => true,
                'pagination' => ['page' => $page, 'limit' => 100, 'total' => 120, 'totalPages' => 2],
                'data' => $records,
            ], 200);
        },
    ]);

    $notification = BirthdayNotification::factory()->create([
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [BirthdayNotificationAudience::BrokerAgents->value],
    ]);

    $job = new ProcessBirthdayNotificationJob(
        birthdayNotificationId: $notification->getKey(),
        referenceDate: '2026-01-18',
    );

    $job->handle(app(\App\Services\Marketing\BirthdayNotificationRecipientCollector::class));

    Queue::assertPushed(SendBirthdayEmailBatchJob::class, 3);
    Queue::assertPushedOn('email', SendBirthdayEmailBatchJob::class);

    Queue::assertPushed(SendBirthdayEmailBatchJob::class, function (SendBirthdayEmailBatchJob $batchJob) use ($notification): bool {
        return $batchJob->birthdayNotificationId === $notification->getKey()
            && $batchJob->totalBatches === 3
            && count($batchJob->recipients) <= 50
            && count($batchJob->recipients) > 0;
    });

    $batchSizes = collect(Queue::pushed(SendBirthdayEmailBatchJob::class))
        ->map(fn (SendBirthdayEmailBatchJob $batchJob): int => count($batchJob->recipients))
        ->sort()
        ->values()
        ->all();

    expect($batchSizes)->toBe([20, 50, 50]);
});

test('process birthday notification job skips when email channel is not configured', function () {
    Queue::fake();

    $notification = BirthdayNotification::factory()->create([
        'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        'audiences' => [BirthdayNotificationAudience::BrokerAgents->value],
    ]);

    (new ProcessBirthdayNotificationJob($notification->getKey()))->handle(
        app(\App\Services\Marketing\BirthdayNotificationRecipientCollector::class),
    );

    Queue::assertNothingPushed();
});

test('send birthday email batch job posts rendered html to bulk endpoint', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 2,
            'total' => 2,
        ], 200),
    ]);

    $notification = BirthdayNotification::factory()->create([
        'name' => 'Cumpleaños TDG',
        'copy' => '¡Feliz cumpleaños!',
        'channels' => [BirthdayNotificationChannel::Email->value],
    ]);

    (new SendBirthdayEmailBatchJob(
        birthdayNotificationId: $notification->getKey(),
        recipients: ['uno@example.com', 'dos@example.com'],
        batchNumber: 1,
        totalBatches: 1,
    ))->handle(
        app(\App\Services\Marketing\BirthdayNotificationBulkEmailService::class),
        app(NotificationDispatchLogger::class),
    );

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && $request->hasFile('logo', null, 'logoTDG.png')
            && str_contains($body, 'uno@example.com')
            && str_contains($body, 'dos@example.com')
            && str_contains($body, '<html')
            && str_contains($body, '¡Feliz cumpleaños!')
            && str_contains($body, 'Cumpleaños TDG');
    });
});
