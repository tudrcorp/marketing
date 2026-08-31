<?php

use App\Filament\Resources\TravelAgents\Pages\ManageTravelAgents;
use App\Jobs\SendMassNotificationEmailBatchJob;
use App\Jobs\SendMassNotificationWhatsAppBatchJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MarketingPermission;
use App\Marketing\MassNotificationContentType;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\MassNotificationRecipientResolver;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
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

function massSendUserWithPermissions(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('recipient resolver extracts email and phone from travel agent records', function () {
    $recipients = app(MassNotificationRecipientResolver::class)->resolveMany(
        BirthdayNotificationAudience::TravelAgents,
        [
            [
                'id' => 29,
                'name' => 'ailyn viña',
                'email' => 'ailynvina@gmail.com',
                'phone' => '04122067511',
            ],
        ],
    );

    expect($recipients)->toHaveCount(1)
        ->and($recipients[0]->sourceId)->toBe('29')
        ->and($recipients[0]->email)->toBe('ailynvina@gmail.com')
        ->and($recipients[0]->phone)->toBe('04122067511')
        ->and($recipients[0]->name)->toBe('ailyn viña');
});

test('dispatch service creates a mass notification and sends email to selected records', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            [
                'id' => 29,
                'name' => 'ailyn viña',
                'email' => 'ailynvina@gmail.com',
                'phone' => '04122067511',
            ],
        ]),
        data: [
            'title' => 'Promoción TDG',
            'copy' => 'Aprovecha nuestra promoción especial.',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    $notification = MassNotification::query()->first();

    expect($result->allSuccessful())->toBeTrue()
        ->and($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Promoción TDG')
        ->and($notification->audiences)->toBe([BirthdayNotificationAudience::TravelAgents->value])
        ->and($notification->recipient_ids)->toBe(['29'])
        ->and($notification->created_by_id)->toBe($user->id);

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return str_ends_with($request->url(), '/api/emails/bulk')
            && str_contains($body, 'ailynvina@gmail.com')
            && str_contains($body, 'Promoción TDG')
            && str_contains($body, 'Aprovecha nuestra promoción especial.');
    });
});

test('dispatch service reports an unconfirmed timeout instead of a hard failure when the api takes too long to respond', function () {
    Http::fake(function () {
        throw new ConnectionException(
            'cURL error 28: Operation timed out after 120002 milliseconds with 0 bytes received (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for http://localhost:4000/api/emails/bulk',
        );
    });

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            [
                'id' => 29,
                'name' => 'ailyn viña',
                'email' => 'ailynvina@gmail.com',
                'phone' => '04122067511',
            ],
        ]),
        data: [
            'title' => 'Promoción TDG',
            'copy' => 'Aprovecha nuestra promoción especial.',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($result->channelResults[0]->successful)->toBeFalse()
        ->and($result->channelResults[0]->message)->toBe('El API de correos no confirmó el resultado a tiempo. Es probable que los correos sí se hayan enviado.');

    $log = NotificationDispatchLog::query()->firstOrFail();

    expect($log->failure_code)->toBe('api_email_timeout_unconfirmed')
        ->and($log->analyst_message)->toContain('probable que los correos sí se hayan enviado');
});

test('dispatch service accepts enum MassNotificationBulkSendTest type when sending email', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            [
                'id' => 29,
                'name' => 'ailyn viña',
                'email' => 'ailynvina@gmail.com',
                'phone' => '04122067511',
            ],
        ]),
        data: [
            'title' => 'Promoción TDG',
            'copy' => 'Aprovecha nuestra promoción especial.',
            'channels' => [BirthdayNotificationChannel::Email],
            'content_type' => MassNotificationContentType::Image,
        ],
        sentBy: $user,
    );

    $notification = MassNotification::query()->first();

    expect($result->allSuccessful())->toBeTrue()
        ->and($notification)->not->toBeNull()
        ->and($notification->content_type)->toBe(MassNotificationContentType::Image->value)
        ->and($notification->contentTypeEnum())->toBe(MassNotificationContentType::Image);
});

test('dispatch service queues whatsapp batches for selected records with phone numbers', function () {
    Queue::fake();

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            [
                'id' => 29,
                'name' => 'ailyn viña',
                'email' => 'ailynvina@gmail.com',
                'phone' => '04122067511',
            ],
        ]),
        data: [
            'title' => 'Aviso TDG',
            'copy' => 'Mensaje por WhatsApp.',
            'channels' => [BirthdayNotificationChannel::WhatsApp->value],
        ],
        sentBy: $user,
    );

    expect($result->allSuccessful())->toBeTrue()
        ->and($result->channelResults[0]->message)->toContain('encolaron');

    Queue::assertPushed(SendMassNotificationWhatsAppBatchJob::class, function (SendMassNotificationWhatsAppBatchJob $job) use ($user): bool {
        return $job->phones === ['584122067511']
            && $job->title === 'Aviso TDG'
            && $job->copy === 'Mensaje por WhatsApp.'
            && $job->sentById === $user->id
            && $job->batchNumber === 1
            && $job->totalBatches === 1;
    });
});

test('dispatch service splits whatsapp recipients into batches of fifty', function () {
    Queue::fake([SendMassNotificationWhatsAppBatchJob::class]);

    config(['services.marketing_api.whatsapp_batch_size' => 50]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

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

    expect($result->queued)->toBeTrue()
        ->and($result->allSuccessful())->toBeTrue()
        ->and($result->notification->recipient_ids)->toHaveCount(51);

    Queue::assertPushed(SendMassNotificationWhatsAppBatchJob::class, 2);
});

test('dispatch service splits mass email recipients into batches instead of sending them all in one request', function () {
    Queue::fake([SendMassNotificationEmailBatchJob::class]);

    config(['services.marketing_api.mass_email_batch_size' => 50]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $records = collect(range(1, 51))
        ->map(fn (int $index): array => [
            'id' => $index,
            'name' => "Contacto {$index}",
            'email' => "contacto{$index}@example.com",
        ])
        ->all();

    $result = app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make($records),
        data: [
            'title' => 'Campaña masiva',
            'copy' => 'Mensaje masivo.',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    expect($result->queued)->toBeTrue()
        ->and($result->allSuccessful())->toBeTrue()
        ->and($result->notification->recipient_ids)->toHaveCount(51);

    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, 2);
    Queue::assertPushedOn('email', SendMassNotificationEmailBatchJob::class);
    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, function (SendMassNotificationEmailBatchJob $job): bool {
        return $job->batchNumber === 1 && $job->totalBatches === 2 && count($job->emails) === 50;
    });
    Queue::assertPushed(SendMassNotificationEmailBatchJob::class, function (SendMassNotificationEmailBatchJob $job): bool {
        return $job->batchNumber === 2 && $job->totalBatches === 2 && count($job->emails) === 1;
    });
});

test('dispatch service logs the immediate channel summary as queued, not sent, while batches are pending', function () {
    Queue::fake([SendMassNotificationEmailBatchJob::class]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            [
                'id' => 1,
                'name' => 'Contacto uno',
                'email' => 'contacto1@example.com',
            ],
            [
                'id' => 2,
                'name' => 'Contacto dos',
                'email' => 'contacto2@example.com',
            ],
        ]),
        data: [
            'title' => 'Campaña masiva',
            'copy' => 'Mensaje masivo.',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    $channelSummaryLog = NotificationDispatchLog::query()->whereNull('batch_number')->first();

    expect($channelSummaryLog)->not->toBeNull()
        ->and($channelSummaryLog->status)->toBe('queued')
        ->and($channelSummaryLog->sent_count)->toBe(0);
});

test('email batch job posts each batch to the bulk endpoint and aggregates progress', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::sequence()
            ->push(['success' => true, 'message' => 'Envío realizado', 'sent' => 2, 'total' => 2], 200)
            ->push(['success' => true, 'message' => 'Envío realizado', 'sent' => 1, 'total' => 1], 200),
    ]);

    config(['services.marketing_api.mass_email_batch_size' => 2]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $records = collect(range(1, 3))
        ->map(fn (int $index): array => [
            'id' => $index,
            'name' => "Contacto {$index}",
            'email' => "contacto{$index}@example.com",
        ])
        ->all();

    app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make($records),
        data: [
            'title' => 'Campaña por lotes',
            'copy' => 'Mensaje masivo.',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    Http::assertSentCount(2);

    // Una fila "encolado" (resumen inmediato del canal) + una fila por lote realmente enviado.
    $logs = NotificationDispatchLog::query()->get();
    $batchLogs = $logs->whereNotNull('batch_number');

    expect($logs)->toHaveCount(3)
        ->and($batchLogs)->toHaveCount(2)
        ->and($batchLogs->sum('sent_count'))->toBe(3)
        ->and($batchLogs->every(fn (NotificationDispatchLog $log): bool => $log->status === 'sent'))->toBeTrue();
});

test('whatsapp batch job posts recipients to marketing api send-batch endpoint', function () {
    Http::fake([
        '*/api/notifications/mass/send-batch' => Http::response([
            'success' => true,
            'accepted' => true,
            'queued' => 2,
            'total' => 2,
        ], 202),
    ]);

    $user = User::factory()->create();
    $notification = MassNotification::factory()->create([
        'created_by_id' => $user->id,
    ]);

    (new SendMassNotificationWhatsAppBatchJob(
        massNotificationId: $notification->id,
        phones: ['584120000001', '584120000002'],
        title: 'Campaña',
        copy: 'Hola',
        attachmentUrl: null,
        batchNumber: 1,
        totalBatches: 1,
        sentById: $user->id,
    ))->handle(
        app(\App\Services\Marketing\NotificationDispatchLogger::class),
        app(\App\Services\Marketing\MarketingApiTraceRecorder::class),
        app(\App\Services\Marketing\DispatchProgressTracker::class),
    );

    Http::assertSent(function ($request): bool {
        return str_ends_with($request->url(), '/api/notifications/mass/send-batch')
            && $request['channel'] === 'whatsapp'
            && $request['recipients'] === ['584120000001', '584120000002']
            && $request['title'] === 'Campaña';
    });
});

test('analyst can send a mass notification from travel agents table bulk action', function () {
    Http::fake([
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 25, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 29,
                    'name' => 'ailyn viña',
                    'email' => 'ailynvina@gmail.com',
                    'phone' => '04122067511',
                    'fechaNacimiento' => '1988-04-23',
                    'age' => 38,
                ],
            ],
        ], 200),
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'message' => 'Envío realizado',
            'sent' => 1,
            'total' => 1,
        ], 200),
    ]);

    $user = massSendUserWithPermissions([
        MarketingPermission::ManageMassNotifications,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ManageTravelAgents::class)
        ->callTableBulkAction('sendMassNotification', ['29'], [
            'title' => 'Campaña desde tabla',
            'copy' => 'Mensaje enviado desde audiencias TDG.',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ])
        ->assertNotified();

    expect(MassNotification::query()->count())->toBe(1);
});
