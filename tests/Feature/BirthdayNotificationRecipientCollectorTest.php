<?php

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use App\Services\Marketing\BirthdayNotificationRecipientCollector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('recipient collector gathers today birthdays with valid emails across audiences', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 10,
                    'name_corporative' => 'Agente Cumpleañero',
                    'birth_date' => '1981-01-18',
                    'email' => 'agente@example.com',
                ],
            ],
        ], 200),
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 29,
                    'name' => 'Agente Viajes',
                    'fechaNacimiento' => '1988-04-23',
                    'email' => 'viajes@example.com',
                ],
            ],
        ], 200),
    ]);

    $notification = BirthdayNotification::factory()->create([
        'channels' => [BirthdayNotificationChannel::Email->value],
        'audiences' => [
            BirthdayNotificationAudience::BrokerAgents->value,
            BirthdayNotificationAudience::TravelAgents->value,
        ],
    ]);

    $recipients = app(BirthdayNotificationRecipientCollector::class)->collect(
        $notification,
        Carbon::create(2026, 1, 18),
    );

    expect($recipients)->toHaveCount(1)
        ->and($recipients[0]->email)->toBe('agente@example.com')
        ->and($recipients[0]->audience)->toBe(BirthdayNotificationAudience::BrokerAgents);
});

test('recipient collector deduplicates emails across audiences', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 1,
                    'name_corporative' => 'Duplicado Uno',
                    'birth_date' => '1981-01-18',
                    'email' => 'duplicado@example.com',
                ],
            ],
        ], 200),
        'http://localhost:4000/api/travel-agents*' => Http::response([
            'success' => true,
            'pagination' => ['page' => 1, 'limit' => 100, 'total' => 1, 'totalPages' => 1],
            'data' => [
                [
                    'id' => 2,
                    'name' => 'Duplicado Dos',
                    'fechaNacimiento' => '1981-01-18',
                    'email' => 'DUPLICADO@example.com',
                ],
            ],
        ], 200),
    ]);

    $notification = BirthdayNotification::factory()->create([
        'audiences' => [
            BirthdayNotificationAudience::BrokerAgents->value,
            BirthdayNotificationAudience::TravelAgents->value,
        ],
    ]);

    $recipients = app(BirthdayNotificationRecipientCollector::class)->collect(
        $notification,
        Carbon::create(2026, 1, 18),
    );

    expect($recipients)->toHaveCount(1);
});

test('recipient collector paginates through all api pages', function () {
    Http::fake([
        'http://localhost:4000/api/agents*' => function ($request) {
            $page = (int) $request->data()['page'];

            return Http::response([
                'success' => true,
                'pagination' => ['page' => $page, 'limit' => 100, 'total' => 150, 'totalPages' => 2],
                'data' => $page === 1
                    ? [[
                        'id' => 1,
                        'name_corporative' => 'Pagina Uno',
                        'birth_date' => '1981-01-18',
                        'email' => 'pagina1@example.com',
                    ]]
                    : [[
                        'id' => 2,
                        'name_corporative' => 'Pagina Dos',
                        'birth_date' => '1981-01-18',
                        'email' => 'pagina2@example.com',
                    ]],
            ], 200);
        },
    ]);

    $notification = BirthdayNotification::factory()->create([
        'audiences' => [BirthdayNotificationAudience::BrokerAgents->value],
    ]);

    $recipients = app(BirthdayNotificationRecipientCollector::class)->collect(
        $notification,
        Carbon::create(2026, 1, 18),
    );

    expect($recipients)->toHaveCount(2)
        ->and(collect($recipients)->pluck('email')->all())->toBe([
            'pagina1@example.com',
            'pagina2@example.com',
        ]);
});
