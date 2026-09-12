<?php

use App\Jobs\SendMassNotificationCampaignJob;
use App\Jobs\SyncMailchimpCampaignReportJob;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\MassNotificationDispatchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('a mailchimp rate limit reschedules the campaign job without duplicating a send', function () {
    Queue::fake();

    Http::fake([
        '*/api/emails/campaigns' => Http::response([
            'success' => false,
            'error' => 'Too many requests',
            'reason' => 'Mailchimp rate limit exceeded',
        ], 429),
    ]);

    $notification = MassNotification::factory()->create([
        'title' => 'Campaña Mailchimp',
        'copy' => 'Contenido',
        'channels' => [BirthdayNotificationChannel::Email->value],
    ]);

    $job = new SendMassNotificationCampaignJob(
        massNotificationId: $notification->getKey(),
        emails: ['uno@example.com', 'dos@example.com'],
        subject: $notification->title,
        copy: '<p>Copy *|UNSUB|*</p>',
        sentById: $notification->created_by_id,
        source: NotificationDispatchSource::MassAudience->value,
    );

    app()->call([$job, 'handle']);

    Queue::assertPushed(SendMassNotificationCampaignJob::class, function (SendMassNotificationCampaignJob $queued): bool {
        return $queued->emails === ['uno@example.com', 'dos@example.com']
            && $queued->attempt === 2
            && $queued->delay !== null;
    });
});

test('a successful campaign stores the mailchimp id and schedules a report sync', function () {
    Queue::fake([SyncMailchimpCampaignReportJob::class]);

    Http::fake([
        '*/api/emails/campaigns' => Http::response([
            'success' => true,
            'campaign_id' => 'camp_ok',
            'message' => 'Mailchimp aceptó la campaña.',
            'sent' => 2,
            'total' => 2,
        ], 200),
    ]);

    $notification = MassNotification::factory()->create([
        'title' => 'Campaña Mailchimp',
        'copy' => 'Contenido',
        'channels' => [BirthdayNotificationChannel::Email->value],
    ]);

    $job = new SendMassNotificationCampaignJob(
        massNotificationId: $notification->getKey(),
        emails: ['uno@example.com', 'dos@example.com'],
        subject: $notification->title,
        copy: '<p>Copy *|UNSUB|*</p>',
        sentById: $notification->created_by_id,
        source: NotificationDispatchSource::MassAudience->value,
    );

    app()->call([$job, 'handle']);

    $log = NotificationDispatchLog::query()->latest('id')->first();

    expect($log->status)->toBe(NotificationDispatchStatus::Sent->value)
        ->and(data_get($log->technical_detail, 'mailchimp_campaign_id'))->toBe('camp_ok');

    Queue::assertPushed(SyncMailchimpCampaignReportJob::class, function (SyncMailchimpCampaignReportJob $queued) use ($notification): bool {
        return $queued->campaignId === 'camp_ok'
            && $queued->massNotificationId === $notification->getKey();
    });
});

test('report sync refreshes the dispatch log with opens bounces and unsubscribes', function () {
    $user = User::factory()->create();
    $notification = MassNotification::factory()->create([
        'created_by_id' => $user->id,
    ]);

    $log = NotificationDispatchLog::factory()->create([
        'mass_notification_id' => $notification->getKey(),
        'channel' => BirthdayNotificationChannel::Email->value,
        'status' => NotificationDispatchStatus::Sent->value,
        'sent_count' => 10,
        'total_count' => 10,
        'technical_detail' => [
            'mailchimp_campaign_id' => 'camp_sync',
            'api_calls' => [],
        ],
    ]);

    Http::fake([
        '*/api/emails/campaigns/camp_sync' => Http::response([
            'success' => true,
            'campaign_id' => 'camp_sync',
            'emails_sent' => 10,
            'opens' => 4,
            'clicks' => 1,
            'bounces' => ['hard' => 1, 'soft' => 0],
            'unsubscribed' => 1,
            'events' => [
                ['type' => 'unsubscribe', 'email' => 'baja@example.com'],
            ],
        ], 200),
    ]);

    app()->call([new SyncMailchimpCampaignReportJob($notification->getKey(), 'camp_sync'), 'handle']);

    $log->refresh();

    expect($log->status)->toBe(NotificationDispatchStatus::Partial->value)
        ->and($log->summary)->toContain('4 abiertos')
        ->and($log->summary)->toContain('1 rebotes duros')
        ->and(data_get($log->technical_detail, 'mailchimp_report.unsubscribed'))->toBe(1);
});

test('report sync counts webhook events when mailchimp report totals are still zero', function () {
    $user = User::factory()->create();
    $notification = MassNotification::factory()->create([
        'created_by_id' => $user->id,
    ]);

    $log = NotificationDispatchLog::factory()->create([
        'mass_notification_id' => $notification->getKey(),
        'channel' => BirthdayNotificationChannel::Email->value,
        'status' => NotificationDispatchStatus::Sent->value,
        'sent_count' => 10,
        'total_count' => 10,
        'technical_detail' => [
            'mailchimp_campaign_id' => 'camp_webhook',
            'api_calls' => [],
        ],
    ]);

    Http::fake([
        '*/api/emails/campaigns/camp_webhook' => Http::response([
            'success' => true,
            'campaign_id' => 'camp_webhook',
            'emails_sent' => 10,
            'opens' => 0,
            'clicks' => 0,
            'bounces' => ['hard' => 0, 'soft' => 0],
            'unsubscribed' => 0,
            'events' => [
                ['type' => 'unsubscribe', 'email' => 'baja@example.com'],
                ['type' => 'open', 'email' => 'lee@example.com'],
                ['type' => 'hard_bounce', 'email' => 'rebote@example.com'],
            ],
        ], 200),
    ]);

    app()->call([new SyncMailchimpCampaignReportJob($notification->getKey(), 'camp_webhook'), 'handle']);

    $log->refresh();

    expect($log->status)->toBe(NotificationDispatchStatus::Partial->value)
        ->and($log->summary)->toContain('1 abiertos')
        ->and($log->summary)->toContain('1 rebotes duros')
        ->and($log->summary)->toContain('1 bajas')
        ->and(data_get($log->technical_detail, 'mailchimp_report.unsubscribed'))->toBe(1)
        ->and(data_get($log->technical_detail, 'mailchimp_report.opens'))->toBe(1);
});

test('mass dispatch of several recipients queues one campaign job', function () {
    Queue::fake([SendMassNotificationCampaignJob::class]);

    $user = User::factory()->create();

    app(MassNotificationDispatchService::class)->dispatch(
        audience: BirthdayNotificationAudience::TravelAgents,
        selectedRecords: Collection::make([
            ['id' => 1, 'name' => 'Uno', 'email' => 'uno@example.com'],
            ['id' => 2, 'name' => 'Dos', 'email' => 'dos@example.com'],
        ]),
        data: [
            'title' => 'Campaña',
            'copy' => 'Mensaje',
            'channels' => [BirthdayNotificationChannel::Email->value],
        ],
        sentBy: $user,
    );

    Queue::assertPushed(SendMassNotificationCampaignJob::class, 1);
});
