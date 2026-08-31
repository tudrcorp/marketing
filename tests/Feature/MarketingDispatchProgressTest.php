<?php

use App\Jobs\SendMassNotificationWhatsAppBatchJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\DispatchProgressStatus;
use App\Marketing\MarketingPermission;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\MarketingApiTraceRecorder;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\NotificationDispatchLogger;
use Database\Seeders\MarketingRoleSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function dispatchProgressUser(): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => [MarketingPermission::ManageMassNotifications],
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('dispatch progress tracker records unit completion and marks run completed', function () {
    $tracker = app(DispatchProgressTracker::class);
    $user = dispatchProgressUser();

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 99,
        title: 'Campaña de prueba',
        totalRecipients: 120,
    );

    $tracker->registerChannelUnits($runId, 'WhatsApp', 2, 'Encolando lotes…');
    $tracker->recordUnitCompletion($runId, 'WhatsApp', 50, 0, 'Lote aceptado', 1, 2);

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs)->toHaveCount(1)
        ->and($runs[0]['percent'])->toBe(42)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Processing->value);

    $tracker->recordUnitCompletion($runId, 'WhatsApp', 70, 0, 'Lote aceptado', 2, 2);

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['percent'])->toBe(100)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Completed->value)
        ->and($runs[0]['sent_recipients'])->toBe(120);
});

test('dispatch service starts progress run only when more than one recipient is selected', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'sent' => 2,
            'total' => 2,
        ], 200),
    ]);

    $user = dispatchProgressUser();
    $service = app(MassNotificationDispatchService::class);
    $tracker = app(DispatchProgressTracker::class);

    $singleRecipientResult = $service->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            ['id' => 1, 'name' => 'Uno', 'email' => 'uno@example.com'],
        ]),
        data: [
            'title' => 'Solo uno',
            'copy' => 'Mensaje',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($singleRecipientResult->dispatchRunId)->toBeNull()
        ->and($singleRecipientResult->queued)->toBeFalse()
        ->and($tracker->getRunsForUser($user->id))->toBe([]);

    $multiRecipientResult = $service->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            ['id' => 1, 'name' => 'Uno', 'email' => 'uno@example.com'],
            ['id' => 2, 'name' => 'Dos', 'email' => 'dos@example.com'],
        ]),
        data: [
            'title' => 'Varios',
            'copy' => 'Mensaje masivo',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($multiRecipientResult->dispatchRunId)->not->toBeNull()
        ->and($multiRecipientResult->queued)->toBeTrue();

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs)->toHaveCount(1)
        ->and($runs[0]['title'])->toBe('Varios')
        ->and($runs[0]['total_recipients'])->toBe(2)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Completed->value);
});

test('whatsapp batch job updates dispatch progress when run id is provided', function () {
    Http::fake([
        '*/api/notifications/mass/send-batch' => Http::response([
            'success' => true,
            'accepted' => true,
            'queued' => 2,
            'total' => 2,
        ], 202),
    ]);

    $user = dispatchProgressUser();
    $notification = MassNotification::factory()->create([
        'created_by_id' => $user->id,
    ]);

    $tracker = app(DispatchProgressTracker::class);
    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: $notification->id,
        title: 'WhatsApp masivo',
        totalRecipients: 2,
    );

    $tracker->registerChannelUnits($runId, 'WhatsApp', 1, 'Enviando lote…');

    (new SendMassNotificationWhatsAppBatchJob(
        massNotificationId: $notification->id,
        phones: ['584120000001', '584120000002'],
        title: 'WhatsApp masivo',
        copy: 'Hola',
        attachmentUrl: null,
        batchNumber: 1,
        totalBatches: 1,
        sentById: $user->id,
        dispatchRunId: $runId,
    ))->handle(
        app(NotificationDispatchLogger::class),
        app(MarketingApiTraceRecorder::class),
        app(DispatchProgressTracker::class),
    );

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['percent'])->toBe(100)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Completed->value)
        ->and($runs[0]['detail'])->toContain('Envío completado');
});

test('dispatch progress tracker dismiss hides run for user', function () {
    $tracker = app(DispatchProgressTracker::class);
    $user = dispatchProgressUser();

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 1,
        title: 'Campaña',
        totalRecipients: 3,
    );

    expect($tracker->getRunsForUser($user->id))->toHaveCount(1);

    $tracker->dismiss($runId, $user->id);

    expect($tracker->getRunsForUser($user->id))->toBe([]);
});

test('email dispatch progress records partial failures from api response', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'sent' => 1,
            'total' => 2,
            'failures' => [
                [
                    'email' => 'ul_jgomez@gmail.com',
                    'reason' => '550 5.1.1 The email account that you tried to reach does not exist. NoSuchUser',
                ],
            ],
        ], 200),
    ]);

    $user = dispatchProgressUser();
    $tracker = app(DispatchProgressTracker::class);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            ['id' => 1, 'name' => 'Uno', 'email' => 'uno@example.com'],
            ['id' => 2, 'name' => 'Dos', 'email' => 'ul_jgomez@gmail.com'],
        ]),
        data: [
            'title' => 'Campaña parcial',
            'copy' => 'Mensaje masivo',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($result->dispatchRunId)->not->toBeNull();

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs)->toHaveCount(1)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Partial->value)
        ->and($runs[0]['percent'])->toBe(100)
        ->and($runs[0]['sent_recipients'])->toBe(1)
        ->and($runs[0]['failed_recipients'])->toBe(1)
        ->and($runs[0]['failures'][0]['recipients'][0]['address'])->toBe('ul_jgomez@gmail.com')
        ->and($runs[0]['failures'][0]['recipients'][0]['analyst_message'])->toContain('no existe');
});

test('dispatch progress tracker records failures for display in floater', function () {
    $tracker = app(DispatchProgressTracker::class);
    $user = dispatchProgressUser();

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 2,
        title: 'Campaña con errores',
        totalRecipients: 10,
    );

    $tracker->registerChannelUnits($runId, 'WhatsApp', 1, 'Enviando…');
    $tracker->recordUnitCompletion($runId, 'WhatsApp', 0, 10, 'El API rechazó el lote', 1, 1);

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['status'])->toBe(DispatchProgressStatus::Failed->value)
        ->and($runs[0]['failed_recipients'])->toBe(10)
        ->and($runs[0]['failures'])->toHaveCount(1)
        ->and($runs[0]['failures'][0]['detail'])->toBe('El servicio de envío rechazó la solicitud.')
        ->and($runs[0]['last_error_summary'])->toBe('El servicio de envío rechazó la solicitud.')
        ->and($runs[0]['show_log_hint'])->toBeTrue()
        ->and($runs[0]['log_hint'])->toContain('Historial de envíos');
});

test('dispatch progress tracker exposes batch errors before job completion', function () {
    $tracker = app(DispatchProgressTracker::class);
    $user = dispatchProgressUser();

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 3,
        title: 'Campaña WhatsApp',
        totalRecipients: 4,
    );

    $tracker->registerChannelUnits($runId, 'WhatsApp', 2, 'Encolando lotes…');
    $tracker->markBatchError(
        runId: $runId,
        channelLabel: 'WhatsApp',
        technicalDetail: 'No se pudo conectar con el API de mensajería.',
        batchNumber: 1,
        totalBatches: 2,
    );

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['detail'])->toContain('No pudimos contactar al servicio de WhatsApp/SMS de TDG.')
        ->and($runs[0]['last_error_summary'])->toBe('No pudimos contactar al servicio de WhatsApp/SMS de TDG.')
        ->and($runs[0]['show_log_hint'])->toBeTrue()
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Processing->value);
});

test('whatsapp batch job failed handler records analyst friendly progress error', function () {
    $user = dispatchProgressUser();
    $tracker = app(DispatchProgressTracker::class);

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 4,
        title: 'WhatsApp masivo',
        totalRecipients: 2,
    );

    $tracker->registerChannelUnits($runId, 'WhatsApp', 1, 'Enviando lote…');

    $job = new SendMassNotificationWhatsAppBatchJob(
        massNotificationId: 4,
        phones: ['584120000001', '584120000002'],
        title: 'WhatsApp masivo',
        copy: 'Hola',
        attachmentUrl: null,
        batchNumber: 1,
        totalBatches: 1,
        sentById: $user->id,
        dispatchRunId: $runId,
    );

    $job->failed(new ConnectionException('Connection timed out'));

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['status'])->toBe(DispatchProgressStatus::Failed->value)
        ->and($runs[0]['last_error_summary'])->toBe('No pudimos contactar al servicio de WhatsApp/SMS de TDG.')
        ->and($runs[0]['show_log_hint'])->toBeTrue();
});

test('dispatch progress floater widget dismisses visible runs', function () {
    $tracker = app(DispatchProgressTracker::class);
    $user = dispatchProgressUser();

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 1,
        title: 'Campaña',
        totalRecipients: 3,
    );

    $this->actingAs($user);

    Livewire::test(\App\Filament\Widgets\MarketingDispatchProgressFloaterWidget::class)
        ->assertSet('runs', fn (array $runs): bool => count($runs) === 1)
        ->call('dismissAll')
        ->assertSet('runs', [])
        ->assertSet('shouldPoll', false);

    expect($tracker->getRunsForUser($user->id))->toBe([]);
});

test('dispatch service registers whatsapp batches in progress tracker for multiple recipients', function () {
    Queue::fake([SendMassNotificationWhatsAppBatchJob::class]);

    config(['services.marketing_api.whatsapp_batch_size' => 50]);

    $user = dispatchProgressUser();
    $tracker = app(DispatchProgressTracker::class);

    $records = collect(range(1, 51))
        ->map(fn (int $index): array => [
            'id' => $index,
            'name' => "Contacto {$index}",
            'phone' => '0412'.str_pad((string) $index, 7, '0', STR_PAD_LEFT),
        ])
        ->all();

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make($records),
        data: [
            'title' => 'Campaña masiva',
            'copy' => 'Mensaje masivo.',
            'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        ],
        sentBy: $user,
    );

    expect($result->dispatchRunId)->not->toBeNull()
        ->and($result->queued)->toBeTrue();

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['total_units'])->toBe(2)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Processing->value);

    Queue::assertPushed(SendMassNotificationWhatsAppBatchJob::class, function (SendMassNotificationWhatsAppBatchJob $job) use ($result): bool {
        return $job->dispatchRunId === $result->dispatchRunId;
    });
});

test('delivery summary mirrors active run progress while email dispatch is processing', function () {
    $user = dispatchProgressUser();
    $tracker = app(DispatchProgressTracker::class);
    $notification = MassNotification::factory()->create([
        'created_by_id' => $user->id,
    ]);

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: $notification->id,
        title: $notification->title,
        totalRecipients: 20,
    );

    $tracker->registerChannelUnits($runId, 'Correo electrónico', 2, 'Enviando correos…');
    $tracker->recordUnitCompletion(
        runId: $runId,
        channelLabel: 'Correo electrónico',
        sent: 10,
        failed: 0,
        detail: 'Lote aceptado',
        batchNumber: 1,
        totalBatches: 2,
        unitKey: 'email-batch-1',
    );

    $summary = app(\App\Services\Marketing\MassNotificationDeliverySummary::class)
        ->summarize($notification, $user->id);

    expect($summary['has_activity'])->toBeTrue()
        ->and($summary['sent'])->toBe(10)
        ->and($summary['total'])->toBe(20)
        ->and($summary['ratio'])->toBe(50.0)
        ->and($summary['active_run']['percent'])->toBe(50)
        ->and($summary['active_run']['status'])->toBe(DispatchProgressStatus::Processing->value);
});

test('dispatch progress percent prefers recipient progress over unit progress', function () {
    $tracker = app(DispatchProgressTracker::class);
    $user = dispatchProgressUser();

    $runId = $tracker->start(
        userId: $user->id,
        massNotificationId: 55,
        title: 'Progreso fino',
        totalRecipients: 40,
    );

    $tracker->registerChannelUnits($runId, 'Correo electrónico', 4, 'Enviando…');
    $tracker->recordUnitCompletion($runId, 'Correo electrónico', 10, 0, 'Lote 1', 1, 4, 'email-batch-1');

    $runs = $tracker->getRunsForUser($user->id);

    expect($runs[0]['percent'])->toBe(25)
        ->and($runs[0]['sent_recipients'])->toBe(10)
        ->and($runs[0]['status'])->toBe(DispatchProgressStatus::Processing->value);
});
