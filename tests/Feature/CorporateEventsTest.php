<?php

use App\Filament\Pages\CorporateEventsCalendar;
use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use App\Filament\Resources\CorporateEvents\Pages\ViewCorporateEvent;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\CorporateEventRegistrationStatus;
use App\Marketing\CorporateEventStatus;
use App\Marketing\MarketingPermission;
use App\Marketing\MassNotificationRecipient;
use App\Models\CorporateEvent;
use App\Models\MarketingRole;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\CorporateEventPromotionService;
use App\Services\Marketing\CorporateEventRegistrationService;
use App\Services\Marketing\MarketingAudienceContactCollector;
use Database\Seeders\MarketingRoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    $this->seed(MarketingRoleSeeder::class);
});

function corporateEventsUser(array $permissions): User
{
    $role = MarketingRole::factory()->create([
        'permissions' => $permissions,
    ]);

    return User::factory()->create([
        'marketing_role_id' => $role->id,
    ]);
}

test('corporate event can be published from draft', function () {
    $event = CorporateEvent::factory()->create([
        'status' => CorporateEventStatus::Draft->value,
    ]);

    app(CorporateEventPromotionService::class)->publish($event);

    $event->refresh();

    expect($event->status)->toBe(CorporateEventStatus::Published->value)
        ->and($event->published_at)->not->toBeNull();
});

test('registration service enforces capacity and deduplicates email', function () {
    $event = CorporateEvent::factory()->published()->create([
        'capacity' => 1,
        'registrations_count' => 0,
    ]);

    $service = app(CorporateEventRegistrationService::class);

    $service->register($event, [
        'full_name' => 'Ana Pérez',
        'email' => 'ana@example.com',
        'phone' => '04141234567',
    ]);

    expect($event->refresh()->registrations_count)->toBe(1);

    expect(fn () => $service->register($event->refresh(), [
        'full_name' => 'Otro Usuario',
        'email' => 'otro@example.com',
    ]))->toThrow(Illuminate\Validation\ValidationException::class);

    expect(fn () => $service->register($event->refresh(), [
        'full_name' => 'Ana Pérez',
        'email' => 'ana@example.com',
    ]))->toThrow(Illuminate\Validation\ValidationException::class);
});

test('registration cancellation decrements event count', function () {
    $event = CorporateEvent::factory()->published()->create([
        'capacity' => 10,
        'registrations_count' => 1,
    ]);

    $registration = $event->registrations()->create([
        'full_name' => 'Carlos Ruiz',
        'email' => 'carlos@example.com',
        'status' => CorporateEventRegistrationStatus::Registered->value,
        'registered_at' => now(),
    ]);

    app(CorporateEventRegistrationService::class)->cancel($registration);

    expect($event->refresh()->registrations_count)->toBe(0)
        ->and($registration->refresh()->status)->toBe(CorporateEventRegistrationStatus::Cancelled->value);
});

test('promotion service dispatches mass notification to audiences', function () {
    Http::fake([
        '*/api/emails/bulk' => Http::response([
            'success' => true,
            'sent' => 1,
            'total' => 1,
            'message' => 'Envío realizado',
        ], 200),
    ]);

    $this->mock(MarketingAudienceContactCollector::class)
        ->shouldReceive('collect')
        ->once()
        ->andReturn([
            new MassNotificationRecipient(
                sourceId: '10',
                name: 'María López',
                audience: BirthdayNotificationAudience::Collaborators,
                email: 'maria@example.com',
                phone: '04141112222',
            ),
        ]);

    $user = corporateEventsUser([MarketingPermission::ManageCorporateEvents]);
    $event = CorporateEvent::factory()->published()->create([
        'target_audiences' => [BirthdayNotificationAudience::Collaborators->value],
    ]);

    $result = app(CorporateEventPromotionService::class)->promote(
        $event,
        [BirthdayNotificationChannel::Email->value],
        $user,
    );

    expect($result->allSuccessful())->toBeTrue()
        ->and(MassNotification::query()->count())->toBe(1);

    $event->refresh();

    expect($event->status)->toBe(CorporateEventStatus::Promoted->value)
        ->and($event->mass_notification_id)->not->toBeNull()
        ->and($event->promoted_channels)->toBe([BirthdayNotificationChannel::Email->value]);
});

test('authorized user can access corporate events resource and calendar', function () {
    $user = corporateEventsUser([
        MarketingPermission::ViewCorporateEvents,
        MarketingPermission::ManageCorporateEvents,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    $this->get(CorporateEventResource::getUrl('index'))
        ->assertSuccessful();

    $this->get(CorporateEventsCalendar::getUrl())
        ->assertSuccessful();
});

test('corporate event view page renders profile hero metrics', function () {
    $user = corporateEventsUser([
        MarketingPermission::ViewCorporateEvents,
    ]);

    $event = CorporateEvent::factory()->published()->create([
        'title' => 'Evento de Capacitacion de Prueba',
        'code' => 'EVNE-260610-SLES',
        'capacity' => 10,
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(ViewCorporateEvent::class, ['record' => $event->getKey()])
        ->assertOk()
        ->assertSee('Evento de Capacitacion de Prueba')
        ->assertSee('EVNE-260610-SLES')
        ->assertSee('Publicado')
        ->assertSee('Inscritos')
        ->assertSee('Asistencia')
        ->assertSee('Audiencias');
});

test('unauthorized user cannot access corporate events resource', function () {
    $user = corporateEventsUser([
        MarketingPermission::ViewCalendar,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    $this->get(CorporateEventResource::getUrl('index'))
        ->assertForbidden();
});
