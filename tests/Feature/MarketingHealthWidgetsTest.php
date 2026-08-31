<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ApiHealthWidget;
use App\Filament\Widgets\DatabaseHealthWidget;
use App\Filament\Widgets\MarketingDashboardHealthIndicatorsWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

it('renders compact health indicators in the marketing dashboard header', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::response(['status' => 'ok'], 200),
        'http://localhost:4000/api/health/db' => Http::response(['status' => 'ok'], 200),
    ]);

    $user = User::factory()->create([
        'name' => 'Gustavo Camacho',
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel('marketing');

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Gustavo Camacho')
        ->assertSee('API Marketing')
        ->assertSee('Base de datos')
        ->assertDontSee('No disponible');
});

it('renders the dashboard health indicators as operative when services respond', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::response(['status' => 'ok'], 200),
        'http://localhost:4000/api/health/db' => Http::response(['status' => 'ok'], 200),
    ]);

    Livewire::test(MarketingDashboardHealthIndicatorsWidget::class)
        ->assertSet('apiHealth.isHealthy', true)
        ->assertSet('databaseHealth.isHealthy', true)
        ->assertSee('API Marketing')
        ->assertSee('Base de datos')
        ->assertSee('OK');
});

it('renders the api health widget as operative when the api responds', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::response(['status' => 'ok'], 200),
    ]);

    Livewire::test(ApiHealthWidget::class)
        ->assertSet('health.isHealthy', true)
        ->assertSee('API Marketing')
        ->assertSee('OK')
        ->assertDontSee('Operativo');
});

it('renders the database health widget as unavailable when the endpoint fails', function () {
    Http::fake([
        'http://localhost:4000/api/health/db' => Http::response(['message' => 'db down'], 500),
    ]);

    Livewire::test(DatabaseHealthWidget::class)
        ->assertSet('health.isHealthy', false)
        ->assertSee('Base de datos')
        ->assertSee('ERROR')
        ->assertSee('No disponible')
        ->assertSet('health.message', 'db down');
});

it('refreshes dashboard health indicators on demand', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::sequence()
            ->push(['status' => 'ok'], 200)
            ->push(['message' => 'down'], 503),
        'http://localhost:4000/api/health/db' => Http::response(['status' => 'ok'], 200),
    ]);

    Livewire::test(MarketingDashboardHealthIndicatorsWidget::class)
        ->assertSet('apiHealth.isHealthy', true)
        ->call('refreshHealth')
        ->assertSet('apiHealth.isHealthy', false)
        ->assertSee('ERROR');
});

it('refreshes api health status on demand', function () {
    Http::fake([
        'http://localhost:4000/api/health' => Http::sequence()
            ->push(['status' => 'ok'], 200)
            ->push(['message' => 'down'], 503),
    ]);

    Livewire::test(ApiHealthWidget::class)
        ->assertSet('health.isHealthy', true)
        ->call('refreshHealth')
        ->assertSet('health.isHealthy', false)
        ->assertSee('ERROR');
});
