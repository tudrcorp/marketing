<?php

namespace App\Filament\Pages;

use App\Filament\Support\ContentPostFormSchema;
use App\Marketing\ContentPillar;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostFormat;
use App\Marketing\ContentPostPriority;
use App\Marketing\ContentPostStatus;
use App\Marketing\MarketingPermission;
use App\Models\Brand;
use App\Models\ContentPost;
use App\Models\User;
use App\Services\Marketing\ContentBoardService;
use App\Services\Marketing\ContentPostWorkflowService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Panel máster de contenido multimarca: alterna entre Modo Enfoque (una marca)
 * y Modo Máster (todas las marcas por urgencia), con tablero Kanban y calendario
 * mensual/semanal con semáforo de carga laboral.
 */
class ContentHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Contenido multimarca';

    protected static ?string $title = 'Contenido multimarca';

    protected static ?string $slug = 'contenido';

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.content-hub';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Url(as: 'modo', keep: true)]
    public string $mode = 'master';

    #[Url(as: 'marca', keep: true)]
    public ?int $brandId = null;

    #[Url(as: 'vista', keep: true)]
    public string $boardView = 'kanban';

    #[Url(as: 'rango', keep: true)]
    public string $calendarView = 'month';

    public string $calendarMonth = '';

    public string $anchorDate = '';

    public string $selectedDate = '';

    #[Url(as: 'lote')]
    public ?string $batching = null;

    #[Url(as: 'prioridad')]
    public ?string $priority = null;

    #[Url(as: 'formato')]
    public ?string $format = null;

    #[Url(as: 'pilar')]
    public ?string $pillar = null;

    #[Url(as: 'bloqueo')]
    public ?string $blocker = null;

    public bool $onlyBlocked = false;

    public string $search = '';

    public function mount(): void
    {
        $this->calendarMonth = now()->format('Y-m');
        $this->anchorDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');

        if ($this->mode === 'focus' && $this->brandId === null) {
            $this->brandId = Brand::query()->active()->orderBy('name')->value('id');
        }
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasMarketingPermission(MarketingPermission::ViewContentPosts);
    }

    protected function board(): ContentBoardService
    {
        return app(ContentBoardService::class);
    }

    protected function workflow(): ContentPostWorkflowService
    {
        return app(ContentPostWorkflowService::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function activeFilters(): array
    {
        return [
            'brand_id' => $this->mode === 'focus' ? $this->brandId : null,
            'priority' => $this->priority,
            'format' => $this->format,
            'pillar' => $this->pillar,
            'blocker' => $this->blocker,
            'batching' => $this->batching,
            'search' => filled($this->search) ? $this->search : null,
            'only_blocked' => $this->onlyBlocked,
        ];
    }

    // --- Navegación de modos y vistas -------------------------------------------------

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['focus', 'master'], strict: true) ? $mode : 'master';

        if ($this->mode === 'focus' && $this->brandId === null) {
            $this->brandId = Brand::query()->active()->orderBy('name')->value('id');
        }
    }

    public function selectBrand(?int $brandId): void
    {
        $this->brandId = $brandId;
        $this->mode = $brandId === null ? 'master' : 'focus';
    }

    public function setBoardView(string $view): void
    {
        $this->boardView = in_array($view, ['kanban', 'calendar'], strict: true) ? $view : 'kanban';
    }

    public function setCalendarView(string $view): void
    {
        $this->calendarView = in_array($view, ['month', 'week'], strict: true) ? $view : 'month';
    }

    public function previousPeriod(): void
    {
        if ($this->calendarView === 'week') {
            $this->anchorDate = Carbon::parse($this->anchorDate)->subWeek()->format('Y-m-d');
            $this->calendarMonth = Carbon::parse($this->anchorDate)->format('Y-m');

            return;
        }

        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')->subMonth()->format('Y-m');
    }

    public function nextPeriod(): void
    {
        if ($this->calendarView === 'week') {
            $this->anchorDate = Carbon::parse($this->anchorDate)->addWeek()->format('Y-m-d');
            $this->calendarMonth = Carbon::parse($this->anchorDate)->format('Y-m');

            return;
        }

        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')->addMonth()->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->calendarMonth = now()->format('Y-m');
        $this->anchorDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function selectDay(string $date): void
    {
        $parsed = Carbon::parse($date);

        $this->selectedDate = $parsed->format('Y-m-d');
        $this->anchorDate = $parsed->format('Y-m-d');
        $this->calendarMonth = $parsed->format('Y-m');
    }

    public function resetFilters(): void
    {
        $this->batching = null;
        $this->priority = null;
        $this->format = null;
        $this->pillar = null;
        $this->blocker = null;
        $this->onlyBlocked = false;
        $this->search = '';
    }

    public function toggleOnlyBlocked(): void
    {
        $this->onlyBlocked = ! $this->onlyBlocked;
    }

    // --- Drag & drop y temporizador ---------------------------------------------------

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function movePost(int $postId, string $status, array $orderedIds = []): void
    {
        $post = ContentPost::query()->find($postId);
        $targetStatus = ContentPostStatus::tryFrom($status);

        if ($post === null || $targetStatus === null) {
            return;
        }

        if (! Gate::check('update', $post)) {
            $this->denyAndRefresh();

            return;
        }

        $this->workflow()->moveToStatus($post, $targetStatus, $orderedIds);

        Notification::make()
            ->title('Pieza movida')
            ->body('"'.$post->title.'" pasó a '.$targetStatus->getLabel().'.')
            ->success()
            ->send();
    }

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorderColumn(string $status, array $orderedIds): void
    {
        $targetStatus = ContentPostStatus::tryFrom($status);

        if ($targetStatus === null || ! $this->canManagePosts()) {
            $this->denyAndRefresh();

            return;
        }

        $this->workflow()->reorderColumn($targetStatus, $orderedIds);
    }

    public function reschedulePost(int $postId, string $date): void
    {
        $post = ContentPost::query()->find($postId);

        if ($post === null) {
            return;
        }

        if (! Gate::check('update', $post)) {
            $this->denyAndRefresh();

            return;
        }

        $this->workflow()->reschedule($post, $date);

        Notification::make()
            ->title('Pieza reprogramada')
            ->body('"'.$post->title.'" quedó agendada el '.$this->formatDayLabel($date).'.')
            ->success()
            ->send();
    }

    public function toggleTimer(int $postId): void
    {
        $post = ContentPost::query()->find($postId);

        if ($post === null) {
            return;
        }

        if (! Gate::check('update', $post)) {
            $this->denyAndRefresh();

            return;
        }

        $post = $this->workflow()->toggleTimer($post);

        Notification::make()
            ->title($post->isTimerRunning() ? 'Temporizador iniciado' : 'Temporizador detenido')
            ->body($post->isTimerRunning()
                ? 'Midiendo el tiempo dedicado a "'.$post->title.'".'
                : 'Tiempo acumulado: '.$post->formattedElapsedTime().' h.')
            ->success()
            ->send();
    }

    protected function denyAndRefresh(): void
    {
        Notification::make()
            ->title('Sin permisos')
            ->body('No tienes permiso para modificar piezas de contenido.')
            ->danger()
            ->send();

        $this->dispatch('$refresh');
    }

    // --- Acciones de formulario -------------------------------------------------------

    public function createPostAction(): Action
    {
        return Action::make('createPost')
            ->label('Nueva pieza')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Nueva pieza de contenido')
            ->modalSubmitActionLabel('Crear pieza')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema(ContentPostFormSchema::components())
            ->fillForm(fn (array $arguments): array => [
                'brand_id' => $this->brandId,
                'status' => $arguments['status'] ?? ContentPostStatus::Idea->value,
                'scheduled_at' => filled($arguments['date'] ?? null)
                    ? Carbon::parse($arguments['date'])->setTime(9, 0)
                    : null,
            ])
            ->authorize(fn (): bool => $this->canManagePosts())
            ->action(function (array $data): void {
                ContentPost::query()->create([
                    ...$data,
                    'created_by_id' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Pieza creada')
                    ->body('La pieza se añadió al tablero de contenido.')
                    ->success()
                    ->send();
            });
    }

    public function editPostAction(): Action
    {
        return Action::make('editPost')
            ->label('Editar')
            ->modalHeading(fn (ContentPost $record): string => $record->title)
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalWidth(Width::TwoExtraLarge)
            ->record(fn (array $arguments): ?ContentPost => $this->resolvePost($arguments))
            ->schema(ContentPostFormSchema::components())
            ->fillForm(fn (ContentPost $record): array => $record->attributesToArray())
            ->authorize(fn (ContentPost $record): bool => Gate::check('update', $record))
            ->action(function (array $data, ContentPost $record): void {
                $record->update($data);

                Notification::make()
                    ->title('Pieza actualizada')
                    ->success()
                    ->send();
            });
    }

    public function deletePostAction(): Action
    {
        return Action::make('deletePost')
            ->label('Eliminar')
            ->color('danger')
            ->icon(Heroicon::OutlinedTrash)
            ->requiresConfirmation()
            ->modalHeading('Eliminar pieza')
            ->modalDescription(fn (ContentPost $record): string => '¿Eliminar "'.$record->title.'"? Esta acción no se puede deshacer.')
            ->modalSubmitActionLabel('Sí, eliminar')
            ->record(fn (array $arguments): ?ContentPost => $this->resolvePost($arguments))
            ->authorize(fn (ContentPost $record): bool => Gate::check('delete', $record))
            ->action(function (ContentPost $record): void {
                $record->delete();

                Notification::make()
                    ->title('Pieza eliminada')
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function resolvePost(array $arguments): ?ContentPost
    {
        $postId = $arguments['post'] ?? null;

        return filled($postId) ? ContentPost::query()->find($postId) : null;
    }

    // --- Datos para la vista ----------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    public function getKanbanColumnsProperty(): array
    {
        return $this->board()->kanbanColumns($this->activeFilters());
    }

    /**
     * @return list<list<array<string, mixed>>>
     */
    public function getMonthWeeksProperty(): array
    {
        return $this->board()->monthWeeks($this->activeFilters(), $this->calendarMonth, $this->selectedDate);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getWeekDaysProperty(): array
    {
        return $this->board()->weekDays($this->activeFilters(), $this->anchorDate, $this->selectedDate);
    }

    /**
     * @return Collection<int, ContentPost>
     */
    public function getSelectedDayPostsProperty(): Collection
    {
        return $this->board()->query($this->activeFilters())
            ->whereDate('scheduled_at', $this->selectedDate)
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * @return Collection<int, ContentPost>
     */
    public function getUrgentQueueProperty(): Collection
    {
        return $this->board()->urgentQueue($this->activeFilters());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getBlockerGroupsProperty(): array
    {
        return $this->board()->blockerGroups($this->activeFilters());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getPillarBalanceProperty(): array
    {
        return $this->board()->pillarBalance($this->activeFilters());
    }

    /**
     * @return array<string, int>
     */
    public function getSummaryProperty(): array
    {
        return $this->board()->summary($this->activeFilters());
    }

    /**
     * @return Collection<int, Brand>
     */
    public function getBrandsProperty(): Collection
    {
        return Brand::query()->active()->orderBy('name')->get();
    }

    public function getActiveBrandProperty(): ?Brand
    {
        return $this->brandId === null ? null : $this->brands->firstWhere('id', $this->brandId);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getBatchingOptionsProperty(): array
    {
        return collect(ContentPostStatus::orderedCases())
            ->filter(fn (ContentPostStatus $status): bool => $status->isOpen())
            ->map(fn (ContentPostStatus $status): array => [
                'value' => $status->value,
                'label' => $status->batchingLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getPriorityOptionsProperty(): array
    {
        return collect(ContentPostPriority::orderedCases())
            ->map(fn (ContentPostPriority $priority): array => [
                'value' => $priority->value,
                'label' => $priority->getLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getFormatOptionsProperty(): array
    {
        return collect(ContentPostFormat::orderedCases())
            ->map(fn (ContentPostFormat $format): array => [
                'value' => $format->value,
                'label' => $format->getLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getPillarOptionsProperty(): array
    {
        return collect(ContentPillar::orderedCases())
            ->map(fn (ContentPillar $pillar): array => [
                'value' => $pillar->value,
                'label' => $pillar->getLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getBlockerOptionsProperty(): array
    {
        return collect(ContentPostBlocker::blockingCases())
            ->map(fn (ContentPostBlocker $blocker): array => [
                'value' => $blocker->value,
                'label' => $blocker->getLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{level: string, label: string, description: string}>
     */
    public function getWorkloadLegendProperty(): array
    {
        return ContentBoardService::workloadLegend();
    }

    /**
     * @return list<string>
     */
    public function getWeekdayLabelsProperty(): array
    {
        return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    }

    public function hasActiveFilters(): bool
    {
        return filled($this->batching)
            || filled($this->priority)
            || filled($this->format)
            || filled($this->pillar)
            || filled($this->blocker)
            || filled($this->search)
            || $this->onlyBlocked;
    }

    public function canManagePosts(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('create', ContentPost::class);
    }

    public function formatDayLabel(string $date): string
    {
        return Carbon::parse($date)->locale('es')->isoFormat('dddd D [de] MMMM');
    }

    public function formatPeriodLabel(): string
    {
        if ($this->calendarView === 'week') {
            $start = Carbon::parse($this->anchorDate)->startOfWeek(Carbon::MONDAY);
            $end = $start->copy()->endOfWeek(Carbon::MONDAY);

            return $start->locale('es')->isoFormat('D MMM').' – '.$end->locale('es')->isoFormat('D MMM YYYY');
        }

        return Carbon::parse($this->calendarMonth.'-01')->locale('es')->isoFormat('MMMM YYYY');
    }

    public function referenceImageUrl(?string $path): ?string
    {
        return blank($path) ? null : Storage::disk('public')->url($path);
    }
}
