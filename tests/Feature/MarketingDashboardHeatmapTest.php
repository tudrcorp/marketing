<?php

use App\Models\CorporateEvent;
use App\Models\EditorialPublication;
use App\Models\SocialAccount;
use App\Services\Marketing\MarketingDashboardHeatmapService;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('heatmap service combines events and publications per day with intensity levels', function () {
    $month = now()->format('Y-m');
    $date = now()->startOfMonth()->addDays(2)->format('Y-m-d');

    CorporateEvent::factory()->create([
        'starts_at' => $date.' 10:00:00',
    ]);

    CorporateEvent::factory()->create([
        'starts_at' => $date.' 15:00:00',
    ]);

    EditorialPublication::factory()->for(SocialAccount::factory())->create([
        'scheduled_at' => $date.' 09:00:00',
    ]);

    $grid = app(MarketingDashboardHeatmapService::class)->buildMonthGrid($month);

    $day = collect($grid['weeks'])
        ->flatten(1)
        ->firstWhere('date', $date);

    expect($day)->not->toBeNull()
        ->and($day['eventsCount'])->toBe(2)
        ->and($day['publicationsCount'])->toBe(1)
        ->and($day['totalCount'])->toBe(3)
        ->and($day['intensity'])->toBe(3);
});

test('heatmap service includes day plan with events and publications', function () {
    $month = now()->format('Y-m');
    $date = now()->startOfMonth()->addDays(2)->format('Y-m-d');

    CorporateEvent::factory()->create([
        'title' => 'Capacitación comercial',
        'starts_at' => $date.' 10:00:00',
    ]);

    EditorialPublication::factory()
        ->for(SocialAccount::factory()->state(['platform' => \App\Marketing\SocialPlatform::Instagram]))
        ->create([
            'title' => 'Post Instagram',
            'scheduled_at' => $date.' 09:00:00',
        ]);

    $grid = app(MarketingDashboardHeatmapService::class)->buildMonthGrid($month);
    $day = collect($grid['weeks'])->flatten(1)->firstWhere('date', $date);

    expect($day['plan']['events'])->toHaveCount(1)
        ->and($day['plan']['events'][0]['title'])->toBe('Capacitación comercial')
        ->and($day['plan']['publications'])->toHaveCount(1)
        ->and($day['plan']['publications'][0]['title'])->toBe('Post Instagram')
        ->and($day['plan']['publications'][0]['platformIcon'])->toContain('instagram.png');
});

test('heatmap intensity increases with more activities on the same day', function () {
    $service = app(MarketingDashboardHeatmapService::class);

    expect($service->resolveIntensity(0))->toBe(0)
        ->and($service->resolveIntensity(1))->toBe(1)
        ->and($service->resolveIntensity(2))->toBe(2)
        ->and($service->resolveIntensity(3))->toBe(3)
        ->and($service->resolveIntensity(4))->toBe(4)
        ->and($service->resolveIntensity(9))->toBe(4);
});
