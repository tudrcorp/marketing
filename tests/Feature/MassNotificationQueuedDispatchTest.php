<?php

use App\Jobs\ProcessMassNotificationChannelsJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\DispatchProgressStatus;
use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use App\Models\User;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\MassNotificationDispatchService;
use Database\Seeders\MarketingRoleSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function queuedDispatchUser(): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => [MarketingPermission::ManageMassNotifications],
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('multi recipient dispatch queues channel processing and starts progress immediately', function () {
    Queue::fake();

    $user = queuedDispatchUser();
    $tracker = app(DispatchProgressTracker::class);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            ['id' => 1, 'name' => 'Uno', 'email' => 'uno@example.com'],
            ['id' => 2, 'name' => 'Dos', 'email' => 'dos@example.com'],
        ]),
        data: [
            'title' => 'Campaña rápida',
            'copy' => 'Mensaje de prueba',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($result->queued)->toBeTrue()
        ->and($result->dispatchRunId)->not->toBeNull();

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs)->toHaveCount(1)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Processing->value)
        ->and($runs[0]['title'])->toBe('Campaña rápida')
        ->and($runs[0]['detail'])->toContain('Preparando envío');

    Queue::assertPushed(ProcessMassNotificationChannelsJob::class, function (ProcessMassNotificationChannelsJob $job) use ($result): bool {
        return $job->dispatchRunId === $result->dispatchRunId
            && $job->massNotificationId === $result->notification->id
            && count($job->recipients) === 2;
    });
});

test('queued channel job sends emails and completes progress run', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 2,
            'total' => 2,
        ], 200),
    ]);

    $user = queuedDispatchUser();
    $tracker = app(DispatchProgressTracker::class);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            ['id' => 1, 'name' => 'Uno', 'email' => 'uno@example.com'],
            ['id' => 2, 'name' => 'Dos', 'email' => 'dos@example.com'],
        ]),
        data: [
            'title' => 'Campaña procesada',
            'copy' => 'Mensaje de prueba',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($result->queued)->toBeTrue();

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['status'])->toBe(DispatchProgressStatus::Completed->value)
        ->and($runs[0]['percent'])->toBe(100)
        ->and($runs[0]['sent_recipients'])->toBe(2);

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/emails/bulk'));
});
