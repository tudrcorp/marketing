<?php

namespace App\Filament\Pages;

use App\Filament\Resources\EditorialPublications\Schemas\EditorialPublicationCalendarForm;
use App\Marketing\MarketingPermission;
use App\Marketing\PublicationStatus;
use App\Marketing\SocialPlatform;
use App\Models\EditorialPublication;
use App\Models\SocialAccount;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class EditorialCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendario editorial';

    protected static ?string $title = 'Calendario editorial';

    protected static ?string $slug = 'calendario-editorial';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.editorial-calendar';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $calendarMonth;

    public string $selectedDate;

    public ?int $socialAccountId = null;

    public ?string $platform = null;

    public function mount(): void
    {
        $this->calendarMonth = now()->format('Y-m');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasMarketingPermission(MarketingPermission::ViewCalendar);
    }

    public function selectDay(string $date): void
    {
        $parsedDate = Carbon::parse($date);

        $this->selectedDate = $parsedDate->format('Y-m-d');
        $this->calendarMonth = $parsedDate->format('Y-m');

        if ($this->selectedDayPublicationsCount === 0 && $this->canCreateOnSelectedDate()) {
            $this->mountAction('createPublication');
        }
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')
            ->subMonth()
            ->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')
            ->addMonth()
            ->format('Y-m');
    }

    public function goToToday(): void
    {
        $this->calendarMonth = now()->format('Y-m');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function createPublicationAction(): Action
    {
        return Action::make('createPublication')
            ->label('Nueva publicación')
            ->modalHeading('Programar publicación')
            ->modalDescription(fn (): string => 'Agenda contenido para el '.$this->formatDayLabel($this->selectedDate).'.')
            ->modalSubmitActionLabel('Guardar borrador')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema(EditorialPublicationCalendarForm::createComponents())
            ->fillForm(fn (): array => [
                'scheduled_at' => Carbon::parse($this->selectedDate)
                    ->timezone(config('app.timezone'))
                    ->setTime(9, 0),
            ])
            ->authorize(fn (): bool => $this->canCreateOnSelectedDate())
            ->action(function (array $data): void {
                if ($this->isPastScheduledAt($data['scheduled_at'])) {
                    Notification::make()
                        ->title('Fecha no válida')
                        ->body('No puedes programar publicaciones en días pasados.')
                        ->danger()
                        ->send();

                    return;
                }

                EditorialPublication::query()->create([
                    ...$data,
                    'status' => PublicationStatus::Draft,
                    'created_by_id' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Publicación agendada')
                    ->body('El borrador quedó programado en el calendario editorial.')
                    ->success()
                    ->send();
            });
    }

    public function viewPublicationAction(): Action
    {
        return Action::make('viewPublication')
            ->label('Ver detalle')
            ->slideOver()
            ->modalWidth(Width::Large)
            ->modalHeading(fn (EditorialPublication $record): string => $record->title)
            ->modalDescription(fn (EditorialPublication $record): string => $record->scheduled_at
                ->timezone(config('app.timezone'))
                ->locale('es')
                ->isoFormat('dddd D [de] MMMM · HH:mm'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->record(fn (array $arguments): ?EditorialPublication => $this->resolvePublicationFromArguments($arguments))
            ->authorize(fn (EditorialPublication $record): bool => Gate::check('view', $record))
            ->modalContent(fn (EditorialPublication $record) => view('filament.pages.partials.editorial-publication-detail', [
                'publication' => $record->loadMissing(['socialAccount', 'createdBy']),
                'canManage' => $this->canManagePublication($record),
            ]));
    }

    public function editPublicationAction(): Action
    {
        return Action::make('editPublication')
            ->label('Editar')
            ->modalHeading('Editar publicación')
            ->modalDescription(fn (EditorialPublication $record): string => 'Actualiza el contenido programado para el '.$record->scheduled_at
                ->timezone(config('app.timezone'))
                ->locale('es')
                ->isoFormat('dddd D [de] MMMM [a las] HH:mm').'.')
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalWidth(Width::TwoExtraLarge)
            ->record(fn (array $arguments): ?EditorialPublication => $this->resolvePublicationFromArguments($arguments))
            ->schema(EditorialPublicationCalendarForm::editComponents())
            ->fillForm(fn (EditorialPublication $record): array => $record->attributesToArray())
            ->authorize(fn (EditorialPublication $record): bool => Gate::check('update', $record))
            ->action(function (array $data, EditorialPublication $record): void {
                $record->update($data);

                Notification::make()
                    ->title('Publicación actualizada')
                    ->body('Los cambios quedaron guardados en el calendario editorial.')
                    ->success()
                    ->send();
            });
    }

    public function deletePublicationAction(): Action
    {
        return Action::make('deletePublication')
            ->label('Eliminar')
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalIconColor('danger')
            ->modalHeading('Eliminar publicación')
            ->modalDescription(fn (EditorialPublication $record): string => '¿Eliminar "'.$record->title.'"? Esta acción no se puede deshacer.')
            ->modalSubmitActionLabel('Sí, eliminar')
            ->record(fn (array $arguments): ?EditorialPublication => $this->resolvePublicationFromArguments($arguments))
            ->authorize(fn (EditorialPublication $record): bool => Gate::check('delete', $record))
            ->action(function (EditorialPublication $record): void {
                $record->delete();

                Notification::make()
                    ->title('Publicación eliminada')
                    ->body('La publicación fue retirada del calendario editorial.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getCalendarWeeksProperty(): array
    {
        $monthStart = Carbon::parse($this->calendarMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::MONDAY);
        $daySummaries = $this->getMonthDaySummaries();

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $dateKey = $cursor->format('Y-m-d');
                $summary = $daySummaries[$dateKey] ?? ['count' => 0, 'platforms' => []];

                $week[] = [
                    'date' => $dateKey,
                    'day' => $cursor->day,
                    'isCurrentMonth' => $cursor->month === $monthStart->month,
                    'isToday' => $cursor->isToday(),
                    'isSelected' => $dateKey === $this->selectedDate,
                    'count' => $summary['count'],
                    'platforms' => $summary['platforms'],
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @return Collection<int, EditorialPublication>
     */
    public function getSelectedDayPublicationsProperty(): Collection
    {
        return $this->basePublicationQuery()
            ->whereDate('scheduled_at', $this->selectedDate)
            ->orderBy('scheduled_at')
            ->get();
    }

    public function getSelectedDayPublicationsCountProperty(): int
    {
        return $this->selectedDayPublications->count();
    }

    /**
     * @return array<string, int>
     */
    public function getMonthPublicationCountsProperty(): array
    {
        return collect($this->getMonthDaySummaries())
            ->mapWithKeys(fn (array $summary, string $date): array => [$date => $summary['count']])
            ->all();
    }

    /**
     * @return array<string, array{count: int, platforms: list<array{value: string, label: string, icon: string}>}>
     */
    protected function getMonthDaySummaries(): array
    {
        $monthStart = Carbon::parse($this->calendarMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $platformOrder = collect(SocialPlatform::orderedCases())
            ->mapWithKeys(fn (SocialPlatform $platform, int $index): array => [$platform->value => $index])
            ->all();

        return $this->basePublicationQuery()
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(fn (EditorialPublication $publication): string => $publication->scheduled_at
                ->timezone(config('app.timezone'))
                ->format('Y-m-d'))
            ->map(function (Collection $publications) use ($platformOrder): array {
                $platforms = $publications
                    ->map(fn (EditorialPublication $publication): SocialPlatform => $publication->socialAccount->platform)
                    ->unique(fn (SocialPlatform $platform): string => $platform->value)
                    ->sortBy(fn (SocialPlatform $platform): int => $platformOrder[$platform->value] ?? 999)
                    ->values()
                    ->map(fn (SocialPlatform $platform): array => [
                        'value' => $platform->value,
                        'label' => $platform->getLabel(),
                        'icon' => $platform->getImageUrl(),
                    ])
                    ->all();

                return [
                    'count' => $publications->count(),
                    'platforms' => $platforms,
                ];
            })
            ->all();
    }

    /**
     * @return Builder<EditorialPublication>
     */
    protected function basePublicationQuery(): Builder
    {
        $query = EditorialPublication::query()
            ->with(['socialAccount:id,name,platform', 'createdBy:id,name'])
            ->select([
                'id',
                'social_account_id',
                'title',
                'body',
                'reference_image',
                'status',
                'scheduled_at',
                'created_by_id',
            ]);

        if (filled($this->socialAccountId)) {
            $query->where('social_account_id', $this->socialAccountId);
        }

        if (filled($this->platform)) {
            $query->whereHas('socialAccount', fn (Builder $builder) => $builder->where('platform', $this->platform));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function resolvePublicationFromArguments(array $arguments): ?EditorialPublication
    {
        $publicationId = $arguments['publication'] ?? null;

        if (! filled($publicationId)) {
            return null;
        }

        return EditorialPublication::query()->find($publicationId);
    }

    public function referenceImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function formatDayLabel(string $dateKey): string
    {
        return Carbon::parse($dateKey)->locale('es')->isoFormat('dddd D [de] MMMM');
    }

    public function formatMonthLabel(): string
    {
        return Carbon::parse($this->calendarMonth.'-01')->locale('es')->isoFormat('MMMM YYYY');
    }

    /**
     * @return array<int, string>
     */
    public function getWeekdayLabelsProperty(): array
    {
        return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    }

    /**
     * @return array<int, string>
     */
    public function getSocialAccountOptionsProperty(): array
    {
        return SocialAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return list<array{value: string, label: string, icon: string}>
     */
    public function getPlatformFilterOptionsProperty(): array
    {
        return collect(SocialPlatform::orderedCases())
            ->map(fn (SocialPlatform $platform): array => [
                'value' => $platform->value,
                'label' => $platform->getLabel(),
                'icon' => $platform->getImageUrl(),
            ])
            ->values()
            ->all();
    }

    public function canCreatePublications(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('create', EditorialPublication::class);
    }

    public function canCreateOnSelectedDate(): bool
    {
        return $this->canCreatePublications() && ! $this->isPastDate($this->selectedDate);
    }

    protected function isPastDate(string $date): bool
    {
        return Carbon::parse($date)
            ->timezone(config('app.timezone'))
            ->startOfDay()
            ->lt(now()->timezone(config('app.timezone'))->startOfDay());
    }

    protected function isPastScheduledAt(mixed $scheduledAt): bool
    {
        if (blank($scheduledAt)) {
            return false;
        }

        return Carbon::parse($scheduledAt)
            ->timezone(config('app.timezone'))
            ->startOfDay()
            ->lt(now()->timezone(config('app.timezone'))->startOfDay());
    }

    public function canViewPublications(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('viewAny', EditorialPublication::class);
    }

    public function canManagePublication(EditorialPublication $publication): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return Gate::forUser($user)->check('update', $publication);
    }
}
