<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventStatus;
use App\Marketing\CorporateEventType;
use App\Marketing\MarketingPermission;
use App\Models\CorporateEvent;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class CorporateEventsCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendario de eventos';

    protected static ?string $title = 'Calendario de eventos';

    protected static ?string $slug = 'calendario-eventos';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.corporate-events-calendar';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $calendarMonth;

    public string $selectedDate;

    public ?string $eventType = null;

    public ?string $modality = null;

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

        return $user->hasMarketingPermission(MarketingPermission::ViewCorporateEvents);
    }

    public function selectDay(string $date): void
    {
        $parsedDate = Carbon::parse($date);

        $this->selectedDate = $parsedDate->format('Y-m-d');
        $this->calendarMonth = $parsedDate->format('Y-m');
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
                $summary = $daySummaries[$dateKey] ?? ['count' => 0, 'types' => []];

                $week[] = [
                    'date' => $dateKey,
                    'day' => $cursor->day,
                    'isCurrentMonth' => $cursor->month === $monthStart->month,
                    'isToday' => $cursor->isToday(),
                    'isSelected' => $dateKey === $this->selectedDate,
                    'count' => $summary['count'],
                    'types' => $summary['types'],
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * @return Collection<int, CorporateEvent>
     */
    public function getSelectedDayEventsProperty(): Collection
    {
        return $this->baseEventQuery()
            ->whereDate('starts_at', $this->selectedDate)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return array<string, array{count: int, types: list<array{value: string, label: string, color: string}>}>
     */
    protected function getMonthDaySummaries(): array
    {
        $monthStart = Carbon::parse($this->calendarMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        return $this->baseEventQuery()
            ->whereBetween('starts_at', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(fn (CorporateEvent $event): string => $event->starts_at
                ->timezone(config('app.timezone'))
                ->format('Y-m-d'))
            ->map(function (Collection $events): array {
                $types = $events
                    ->map(fn (CorporateEvent $event): ?CorporateEventType => $event->typeEnum())
                    ->filter()
                    ->unique(fn (CorporateEventType $type): string => $type->value)
                    ->values()
                    ->map(fn (CorporateEventType $type): array => [
                        'value' => $type->value,
                        'label' => $type->getLabel(),
                        'color' => $type->getColor(),
                    ])
                    ->all();

                return [
                    'count' => $events->count(),
                    'types' => $types,
                ];
            })
            ->all();
    }

    /**
     * @return Builder<CorporateEvent>
     */
    protected function baseEventQuery(): Builder
    {
        $query = CorporateEvent::query()
            ->select([
                'id',
                'title',
                'code',
                'event_type',
                'modality',
                'status',
                'starts_at',
                'ends_at',
                'venue_name',
                'registrations_count',
                'capacity',
            ]);

        if (filled($this->eventType)) {
            $query->where('event_type', $this->eventType);
        }

        if (filled($this->modality)) {
            $query->where('modality', $this->modality);
        }

        return $query->whereNot('status', CorporateEventStatus::Cancelled->value);
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
     * @return list<array{value: string, label: string}>
     */
    public function getEventTypeFilterOptionsProperty(): array
    {
        return collect(CorporateEventType::cases())
            ->map(fn (CorporateEventType $type): array => [
                'value' => $type->value,
                'label' => $type->getLabel(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function getModalityFilterOptionsProperty(): array
    {
        return collect(CorporateEventModality::cases())
            ->map(fn (CorporateEventModality $modality): array => [
                'value' => $modality->value,
                'label' => $modality->getLabel(),
            ])
            ->values()
            ->all();
    }

    public function eventViewUrl(CorporateEvent $event): string
    {
        return CorporateEventResource::getUrl('view', ['record' => $event]);
    }
}
