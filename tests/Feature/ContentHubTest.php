<?php

use App\Filament\Pages\ContentHub;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostPriority;
use App\Marketing\ContentPostStatus;
use App\Marketing\MarketingPermission;
use App\Models\Brand;
use App\Models\ContentPost;
use App\Models\MarketingRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Concerns\SafeRefreshDatabase;

uses(SafeRefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('marketing');
});

function contentHubUser(array $permissions): User
{
    $role = MarketingRole::factory()->create(['permissions' => $permissions]);

    return User::factory()->create(['marketing_role_id' => $role->id]);
}

function contentManager(): User
{
    return contentHubUser([
        MarketingPermission::ViewContentPosts,
        MarketingPermission::ManageContentPosts,
        MarketingPermission::ViewBrands,
    ]);
}

test('el panel de contenido exige el permiso de lectura', function () {
    $viewer = contentHubUser([MarketingPermission::ViewContentPosts]);
    $outsider = contentHubUser([MarketingPermission::ViewCalendar]);

    expect(ContentHub::canAccess())->toBeFalse();

    $this->actingAs($viewer);
    expect(ContentHub::canAccess())->toBeTrue();

    $this->actingAs($outsider);
    expect(ContentHub::canAccess())->toBeFalse();
});

test('el tablero agrupa las piezas en las seis columnas del flujo', function () {
    $brand = Brand::factory()->create();

    ContentPost::factory()->for($brand)->status(ContentPostStatus::Idea)->count(2)->create();
    ContentPost::factory()->for($brand)->status(ContentPostStatus::Approval)->create();

    $columns = Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->assertOk()
        ->instance()
        ->kanbanColumns;

    expect($columns)->toHaveCount(6)
        ->and(collect($columns)->firstWhere('status', ContentPostStatus::Idea)['count'])->toBe(2)
        ->and(collect($columns)->firstWhere('status', ContentPostStatus::Approval)['count'])->toBe(1)
        ->and(collect($columns)->firstWhere('status', ContentPostStatus::Published)['count'])->toBe(0);
});

test('el modo enfoque limita el tablero a la marca seleccionada', function () {
    $salud = Brand::factory()->create(['name' => 'TDG Salud']);
    $viajes = Brand::factory()->create(['name' => 'TDG Viajes']);

    ContentPost::factory()->for($salud)->status(ContentPostStatus::Idea)->count(3)->create();
    ContentPost::factory()->for($viajes)->status(ContentPostStatus::Idea)->count(2)->create();

    $component = Livewire::actingAs(contentManager())
        ->test(ContentHub::class);

    expect($component->instance()->summary['total'])->toBe(5);

    $component->call('selectBrand', $salud->id)
        ->assertSet('mode', 'focus')
        ->assertSet('brandId', $salud->id);

    expect($component->instance()->summary['total'])->toBe(3);
});

test('el filtro por lotes deja solo las piezas del tipo de trabajo elegido', function () {
    $brand = Brand::factory()->create();

    ContentPost::factory()->for($brand)->status(ContentPostStatus::Writing)->count(2)->create();
    ContentPost::factory()->for($brand)->status(ContentPostStatus::Design)->count(4)->create();

    $component = Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->set('batching', ContentPostStatus::Writing->value);

    expect($component->instance()->summary['total'])->toBe(2);

    $component->call('resetFilters');

    expect($component->instance()->summary['total'])->toBe(6);
});

test('el visor de bloqueos agrupa los cuellos de botella activos', function () {
    $brand = Brand::factory()->create();

    ContentPost::factory()->for($brand)->blocked(ContentPostBlocker::MissingClientVideo)->count(2)->create();
    ContentPost::factory()->for($brand)->blocked(ContentPostBlocker::MissingArt)->create();
    ContentPost::factory()->for($brand)->count(3)->create();

    $groups = Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->instance()
        ->blockerGroups;

    expect($groups)->toHaveCount(2)
        ->and(collect($groups)->firstWhere('blocker', ContentPostBlocker::MissingClientVideo)['count'])->toBe(2)
        ->and(collect($groups)->firstWhere('blocker', ContentPostBlocker::MissingArt)['count'])->toBe(1);
});

test('el calendario alterna entre vista mensual y semanal', function () {
    $component = Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->call('setBoardView', 'calendar')
        ->assertSet('boardView', 'calendar')
        ->assertSet('calendarView', 'month');

    expect($component->instance()->monthWeeks)->not->toBeEmpty();

    $component->call('setCalendarView', 'week')
        ->assertSet('calendarView', 'week');

    expect($component->instance()->weekDays)->toHaveCount(7);
});

test('la navegación semanal avanza una semana y la mensual un mes', function () {
    $component = Livewire::actingAs(contentManager())
        ->test(ContentHub::class);

    $startMonth = $component->instance()->calendarMonth;
    $component->call('nextPeriod');
    expect($component->instance()->calendarMonth)
        ->toBe(Carbon::parse($startMonth.'-01')->addMonth()->format('Y-m'));

    $component->call('goToToday')->call('setCalendarView', 'week');
    $startWeek = $component->instance()->anchorDate;
    $component->call('nextPeriod');

    expect($component->instance()->anchorDate)
        ->toBe(Carbon::parse($startWeek)->addWeek()->format('Y-m-d'));
});

test('el semáforo marca como saturados los días con mucha carga', function () {
    $brand = Brand::factory()->create();
    $day = now()->startOfMonth()->addDays(10)->setTime(9, 0);

    ContentPost::factory()->for($brand)->count(8)->create(['scheduled_at' => $day]);

    $weeks = Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->instance()
        ->monthWeeks;

    $cell = collect($weeks)->flatten(1)->firstWhere('date', $day->format('Y-m-d'));

    expect($cell['count'])->toBe(8)
        ->and($cell['workload'])->toBe('saturated');
});

test('arrastrar una tarjeta cambia su estado y persiste el orden de la columna', function () {
    $brand = Brand::factory()->create();

    $moved = ContentPost::factory()->for($brand)->status(ContentPostStatus::Idea)->create();
    $existing = ContentPost::factory()->for($brand)->status(ContentPostStatus::Design)->create();

    Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->call('movePost', $moved->id, ContentPostStatus::Design->value, [$existing->id, $moved->id])
        ->assertHasNoErrors();

    expect($moved->fresh()->status)->toBe(ContentPostStatus::Design)
        ->and($moved->fresh()->board_position)->toBe(1)
        ->and($existing->fresh()->board_position)->toBe(0);
});

test('mover una pieza a publicado sella la fecha de publicación', function () {
    $post = ContentPost::factory()->status(ContentPostStatus::Scheduled)->create();

    Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->call('movePost', $post->id, ContentPostStatus::Published->value);

    expect($post->fresh()->published_at)->not->toBeNull();
});

test('un usuario sin permiso de gestión no puede mover piezas', function () {
    $viewer = contentHubUser([MarketingPermission::ViewContentPosts]);
    $post = ContentPost::factory()->status(ContentPostStatus::Idea)->create();

    Livewire::actingAs($viewer)
        ->test(ContentHub::class)
        ->call('movePost', $post->id, ContentPostStatus::Published->value);

    expect($post->fresh()->status)->toBe(ContentPostStatus::Idea);
});

test('soltar una tarjeta en otro día la reprograma conservando la hora', function () {
    $post = ContentPost::factory()->create([
        'scheduled_at' => now()->setTime(15, 30),
    ]);

    $target = now()->addDays(4)->format('Y-m-d');

    Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->call('reschedulePost', $post->id, $target);

    expect($post->fresh()->scheduled_at->format('Y-m-d H:i'))->toBe($target.' 15:30');
});

test('el temporizador acumula el tiempo trabajado al detenerse', function () {
    $post = ContentPost::factory()->create([
        'time_spent_seconds' => 120,
        'timer_started_at' => null,
    ]);

    $component = Livewire::actingAs(contentManager())->test(ContentHub::class);

    $component->call('toggleTimer', $post->id);
    expect($post->fresh()->isTimerRunning())->toBeTrue();

    $this->travel(10)->minutes();
    $component->call('toggleTimer', $post->id);

    expect($post->fresh()->isTimerRunning())->toBeFalse()
        ->and($post->fresh()->time_spent_seconds)->toBe(720);
});

test('la cola global ordena por urgencia y luego por fecha', function () {
    $brand = Brand::factory()->create();

    ContentPost::factory()->for($brand)->create([
        'title' => 'Media',
        'priority' => ContentPostPriority::Medium,
        'status' => ContentPostStatus::Idea,
        'scheduled_at' => now()->addDay(),
    ]);

    ContentPost::factory()->for($brand)->create([
        'title' => 'Urgente',
        'priority' => ContentPostPriority::Urgent,
        'status' => ContentPostStatus::Idea,
        'scheduled_at' => now()->addWeek(),
    ]);

    $queue = Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->instance()
        ->urgentQueue;

    expect($queue->first()->title)->toBe('Urgente');
});

test('el tablero expone al JS los marcadores de arrastre que necesita', function () {
    $brand = Brand::factory()->create();
    $post = ContentPost::factory()->for($brand)->status(ContentPostStatus::Idea)->create();

    Livewire::actingAs(contentManager())
        ->test(ContentHub::class)
        ->assertSee('data-kanban-column="true"', escape: false)
        ->assertSee('data-status="idea"', escape: false)
        ->assertSee('data-post-id="'.$post->id.'"', escape: false);
});

test('quien solo puede leer recibe las columnas sin arrastre', function () {
    $brand = Brand::factory()->create();
    ContentPost::factory()->for($brand)->status(ContentPostStatus::Idea)->create();

    Livewire::actingAs(contentHubUser([MarketingPermission::ViewContentPosts]))
        ->test(ContentHub::class)
        ->assertSee('data-kanban-column="false"', escape: false)
        ->assertDontSee('data-kanban-column="true"', escape: false);
});

test('el script de arrastre no depende de alpine:init', function () {
    // En el panel SPA, Livewire inyecta este script al navegar (mergeNewHead),
    // cuando Alpine ya arrancó: un listener de `alpine:init` nunca se dispararía
    // y el tablero se quedaría sin drag & drop.
    $script = file_get_contents(resource_path('js/content-hub.js'));

    expect($script)
        ->not->toContain("addEventListener('alpine:init'")
        ->and($script)->toContain('livewire:navigated')
        ->and($script)->toContain('data-kanban-column')
        ->and($script)->toContain("call('movePost'")
        ->and($script)->toContain("call('reorderColumn'");
});
