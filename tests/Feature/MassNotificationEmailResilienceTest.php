<?php

use App\Jobs\SendMassNotificationEmailBatchJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\EmailDispatchFailureKind;
use App\Marketing\MassNotificationRecipient;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\MassEmailCircuitBreaker;
use App\Services\Marketing\MassNotificationDispatchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

const QUOTA_ERROR = 'Data command failed: 550-5.4.5 Daily user sending limit exceeded. For more information on Gmail sending limits go to https://support.google.com/a/answer/166852';

function resilienceNotification(): MassNotification
{
    return MassNotification::factory()->create([
        'title' => 'Campaña de prueba',
        'copy' => 'Contenido de la campaña',
        'channels' => [BirthdayNotificationChannel::Email->value],
    ]);
}

/**
 * @param  list<string>  $emails
 */
function emailBatchJob(MassNotification $notification, array $emails, int $attempt = 1, ?string $runId = null): SendMassNotificationEmailBatchJob
{
    return new SendMassNotificationEmailBatchJob(
        massNotificationId: $notification->getKey(),
        emails: $emails,
        subject: $notification->title,
        copy: 'Contenido de la campaña',
        batchNumber: 1,
        totalBatches: 3,
        sentById: $notification->created_by_id,
        source: NotificationDispatchSource::MassAudience->value,
        dispatchRunId: $runId,
        attempt: $attempt,
    );
}

test('a quota rejection reschedules only the recipients that did not receive the email', function () {
    Queue::fake();

    // El API responde 207: 30 salieron y 20 chocaron contra la cuota diaria.
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => false,
            'sent' => 30,
            'total' => 50,
            'reason' => 'Se enviaron 30 de 50 correos',
            'failures' => [
                ['email' => 'pendiente1@example.com', 'reason' => QUOTA_ERROR],
                ['email' => 'pendiente2@example.com', 'reason' => QUOTA_ERROR],
            ],
        ], 207),
    ]);

    $notification = resilienceNotification();
    $emails = ['enviado@example.com', 'pendiente1@example.com', 'pendiente2@example.com'];

    app()->call([emailBatchJob($notification, $emails), 'handle']);

    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, function (SendMassNotificationEmailBatchJob $job): bool {
        // Lo esencial: el que ya recibió el correo no vuelve a entrar en la cola.
        return $job->emails === ['pendiente1@example.com', 'pendiente2@example.com']
            && $job->attempt === 2
            && $job->delay !== null;
    });
});

test('a quota rejection opens the circuit so the remaining batches never hit the provider', function () {
    Queue::fake();

    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => false,
            'sent' => 0,
            'total' => 2,
            'reason' => QUOTA_ERROR,
        ], 502),
    ]);

    $notification = resilienceNotification();

    app()->call([emailBatchJob($notification, ['uno@example.com', 'dos@example.com']), 'handle']);

    $breaker = app(MassEmailCircuitBreaker::class);

    expect($breaker->isOpen())->toBeTrue()
        ->and($breaker->kind())->toBe(EmailDispatchFailureKind::QuotaExceeded)
        ->and($breaker->secondsUntilRetry())->toBeGreaterThan(3000);
});

test('with the circuit open a batch is deferred without calling the api', function () {
    Queue::fake();
    Http::fake();

    $notification = resilienceNotification();

    app(MassEmailCircuitBreaker::class)->trip(
        kind: EmailDispatchFailureKind::QuotaExceeded,
        reason: QUOTA_ERROR,
        cooldownSeconds: 3600,
    );

    app()->call([emailBatchJob($notification, ['uno@example.com', 'dos@example.com']), 'handle']);

    // Esto es lo que evita repetir el incidente: 7 lotes se quemaron en 2 segundos
    // porque cada uno llamó al API pese a que el proveedor ya había cortado.
    Http::assertNothingSent();

    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, function (SendMassNotificationEmailBatchJob $job): bool {
        return $job->emails === ['uno@example.com', 'dos@example.com']
            && $job->attempt === 1
            && $job->deferrals === 1;
    });
});

test('a permanent rejection is not retried', function () {
    Queue::fake();

    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => false,
            'sent' => 0,
            'total' => 1,
            'reason' => 'The email account that you tried to reach does not exist. 550 5.1.1',
        ], 502),
    ]);

    $notification = resilienceNotification();

    app()->call([emailBatchJob($notification, ['inexistente@example.com']), 'handle']);

    Queue::assertNotPushed(SendMassNotificationEmailBatchJob::class);
    expect(app(MassEmailCircuitBreaker::class)->isOpen())->toBeFalse();

    $log = NotificationDispatchLog::query()->latest('id')->first();

    expect($log->status)->toBe(NotificationDispatchStatus::Failed->value);
});

test('the batch gives up after exhausting its attempts instead of retrying forever', function () {
    Queue::fake();

    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => false,
            'sent' => 0,
            'total' => 1,
            'reason' => QUOTA_ERROR,
        ], 502),
    ]);

    $notification = resilienceNotification();

    app()->call([emailBatchJob($notification, ['uno@example.com'], attempt: 6), 'handle']);

    Queue::assertNotPushed(SendMassNotificationEmailBatchJob::class);

    $log = NotificationDispatchLog::query()->latest('id')->first();

    expect($log->status)->toBe(NotificationDispatchStatus::Failed->value);
});

test('a rescheduled batch is logged as queued with guidance against relaunching', function () {
    Queue::fake();

    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => false,
            'sent' => 0,
            'total' => 1,
            'reason' => QUOTA_ERROR,
        ], 502),
    ]);

    $notification = resilienceNotification();

    app()->call([emailBatchJob($notification, ['uno@example.com']), 'handle']);

    $log = NotificationDispatchLog::query()->latest('id')->first();

    expect($log->status)->toBe(NotificationDispatchStatus::Queued->value)
        ->and($log->summary)->toContain('reencolado')
        ->and($log->failure_code)->toBe('email_batch_rescheduled');
});

test('the progress floater keeps the batch open while it waits, without counting it as failed', function () {
    Queue::fake();

    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => false,
            'sent' => 0,
            'total' => 2,
            'reason' => QUOTA_ERROR,
        ], 502),
    ]);

    $notification = resilienceNotification();
    $user = User::query()->find($notification->created_by_id);
    $tracker = app(DispatchProgressTracker::class);

    $runId = $tracker->start($user->id, $notification->getKey(), $notification->title, 2);
    $tracker->registerChannelUnits($runId, BirthdayNotificationChannel::Email->getLabel(), 1, 'Encolando 1 lote(s) de correo…');

    app()->call([emailBatchJob($notification, ['uno@example.com', 'dos@example.com'], runId: $runId), 'handle']);

    $run = $tracker->getRunsForUser($user->id)[0];

    expect($run['failed_recipients'])->toBe(0)
        ->and($run['completed_units'])->toBe(0)
        ->and($run['detail'])->toContain('reencolado');
});

test('email batches are staggered instead of being queued all at once', function () {
    Queue::fake();
    config()->set('services.marketing_api.mass_email_batch_size', 1);
    config()->set('services.marketing_api.mass_email_batch_pause_seconds', 5);

    $notification = resilienceNotification();
    $user = User::query()->find($notification->created_by_id);
    $runId = app(DispatchProgressTracker::class)->start($user->id, $notification->getKey(), $notification->title, 3);

    app(MassNotificationDispatchService::class)->processChannels(
        notification: $notification,
        recipients: [
            new MassNotificationRecipient(sourceId: '1', name: 'Uno', audience: BirthdayNotificationAudience::Collaborators, email: 'uno@example.com'),
            new MassNotificationRecipient(sourceId: '2', name: 'Dos', audience: BirthdayNotificationAudience::Collaborators, email: 'dos@example.com'),
            new MassNotificationRecipient(sourceId: '3', name: 'Tres', audience: BirthdayNotificationAudience::Collaborators, email: 'tres@example.com'),
        ],
        sentBy: $user,
        source: NotificationDispatchSource::MassAudience,
        dispatchRunId: $runId,
    );

    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, 3);

    // El primero sale de inmediato; los siguientes esperan su turno.
    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, fn ($job) => $job->batchNumber === 1 && $job->delay === null);
    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, fn ($job) => $job->batchNumber === 2 && $job->delay !== null);
    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, fn ($job) => $job->batchNumber === 3 && $job->delay !== null);
});
