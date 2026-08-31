<?php

use App\Livewire\CorporateEvents\PublicRegistration;
use App\Marketing\CorporateEventRegistrationGate;
use App\Marketing\CorporateEventStatus;
use App\Models\CorporateEvent;
use App\Models\CorporateEventRegistration;
use App\Services\Marketing\CorporateEventRegistrationUrlService;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

test('each corporate event receives a unique public registration url on factory create', function () {
    $event = CorporateEvent::factory()->published()->create([
        'registration_deadline' => now()->addWeek(),
    ]);

    expect($event->registration_token)->not->toBeEmpty()
        ->and($event->registration_url)->toContain($event->registration_token);
});

test('guests can register through the public form before the deadline', function () {
    $urlService = app(CorporateEventRegistrationUrlService::class);
    $token = $urlService->generateToken();

    $event = CorporateEvent::factory()->published()->create([
        'registration_token' => $token,
        'registration_url' => $urlService->buildUrl($token),
        'registration_deadline' => now()->addDays(3),
        'capacity' => 20,
        'registrations_count' => 0,
    ]);

    Livewire::test(PublicRegistration::class, ['token' => $token])
        ->set('full_name', 'María Pérez')
        ->set('document_id', 'V-12345678')
        ->set('phone', '04141234567')
        ->set('email', 'maria@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(CorporateEventRegistration::query()->where('corporate_event_id', $event->id)->count())->toBe(1)
        ->and($event->refresh()->registrations_count)->toBe(1);
});

test('public registration is blocked after the registration deadline', function () {
    $urlService = app(CorporateEventRegistrationUrlService::class);
    $token = $urlService->generateToken();

    CorporateEvent::factory()->published()->create([
        'registration_token' => $token,
        'registration_url' => $urlService->buildUrl($token),
        'registration_deadline' => now()->subHour(),
    ]);

    Livewire::test(PublicRegistration::class, ['token' => $token])
        ->assertSet('closedReason', fn (?string $reason): bool => filled($reason));
});

test('public registration page returns not found for invalid token', function () {
    $this->get(route('corporate-events.register', ['token' => 'token-invalido']))
        ->assertNotFound();
});

test('registration stays open until the deadline in the application timezone', function () {
    config(['app.timezone' => 'America/Caracas']);

    $this->travelTo(now()->timezone('America/Caracas')->setTime(19, 30));

    $event = CorporateEvent::factory()->published()->create([
        'registration_deadline' => now()->timezone('America/Caracas')->setTime(20, 0),
    ]);

    expect(app(CorporateEventRegistrationGate::class)->isOpen($event))->toBeTrue();
});

test('draft events do not accept public registrations', function () {
    $urlService = app(CorporateEventRegistrationUrlService::class);
    $token = $urlService->generateToken();

    CorporateEvent::factory()->create([
        'status' => CorporateEventStatus::Draft->value,
        'registration_token' => $token,
        'registration_url' => $urlService->buildUrl($token),
        'registration_deadline' => now()->addWeek(),
    ]);

    Livewire::test(PublicRegistration::class, ['token' => $token])
        ->assertSet('closedReason', 'Este evento aún no está publicado. Publícalo desde el panel de marketing para abrir las inscripciones.');
});
