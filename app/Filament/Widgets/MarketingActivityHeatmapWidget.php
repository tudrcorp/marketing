<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CorporateEventsCalendar;
use App\Filament\Pages\EditorialCalendar;
use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use App\Filament\Resources\EditorialPublications\EditorialPublicationResource;
use App\Marketing\MarketingPermission;
use App\Models\User;
use App\Services\Marketing\MarketingDashboardHeatmapService;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class MarketingActivityHeatmapWidget extends Widget
{
    /**
     * Filtros disponibles para acotar qué actividad se pinta en el calendario.
     */
    public const FILTER_ALL = 'all';

    public const FILTER_EVENTS = 'events';

    public const FILTER_PUBLICATIONS = 'publications';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.marketing-activity-heatmap';

    public string $calendarMonth;

    public string $activityFilter = self::FILTER_ALL;

    public function mount(): void
    {
        $this->calendarMonth = now()->format('Y-m');
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasMarketingPermission(MarketingPermission::ViewCalendar)
            || $user->hasMarketingPermission(MarketingPermission::ViewCorporateEvents);
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')
            ->subMonth()
            ->format('Y-m');

        $this->dispatch('heatmap-refreshed');
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = Carbon::parse($this->calendarMonth.'-01')
            ->addMonth()
            ->format('Y-m');

        $this->dispatch('heatmap-refreshed');
    }

    public function goToToday(): void
    {
        $this->calendarMonth = now()->format('Y-m');

        $this->dispatch('heatmap-refreshed');
    }

    public function setActivityFilter(string $filter): void
    {
        $this->activityFilter = in_array($filter, [self::FILTER_EVENTS, self::FILTER_PUBLICATIONS], true)
            ? $filter
            : self::FILTER_ALL;

        $this->dispatch('heatmap-refreshed');
    }

    public function isViewingCurrentMonth(): bool
    {
        return $this->calendarMonth === now()->format('Y-m');
    }

    /**
     * @return array{
     *     weeks: list<list<array<string, mixed>>>,
     *     maxTotal: int,
     * }
     */
    public function getHeatmapProperty(): array
    {
        return app(MarketingDashboardHeatmapService::class)->buildMonthGrid(
            calendarMonth: $this->calendarMonth,
            includeEvents: $this->showsEvents(),
            includePublications: $this->showsPublications(),
        );
    }

    /**
     * Semanas con al menos un día del mes visible: evita la fila fantasma final.
     *
     * @return list<list<array<string, mixed>>>
     */
    public function getVisibleWeeksProperty(): array
    {
        return array_values(array_filter(
            $this->heatmap['weeks'],
            fn (array $week): bool => collect($week)->contains(fn (array $day): bool => $day['isCurrentMonth']),
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getCurrentMonthDaysProperty(): array
    {
        return collect($this->heatmap['weeks'])
            ->flatten(1)
            ->filter(fn (array $day): bool => $day['isCurrentMonth'])
            ->values()
            ->all();
    }

    /**
     * Resumen del mes usado en la cabecera del widget.
     *
     * @return array{
     *     events: int,
     *     publications: int,
     *     total: int,
     *     activeDays: int,
     *     busiestDate: ?string,
     *     busiestCount: int,
     * }
     */
    public function getMonthSummaryProperty(): array
    {
        $days = collect($this->currentMonthDays);
        $busiest = $days->sortByDesc('totalCount')->first();

        return [
            'events' => (int) $days->sum('eventsCount'),
            'publications' => (int) $days->sum('publicationsCount'),
            'total' => (int) $days->sum('totalCount'),
            'activeDays' => $days->filter(fn (array $day): bool => $day['totalCount'] > 0)->count(),
            'busiestDate' => ($busiest && $busiest['totalCount'] > 0) ? $busiest['date'] : null,
            'busiestCount' => ($busiest && $busiest['totalCount'] > 0) ? (int) $busiest['totalCount'] : 0,
        ];
    }

    public function getMonthTotalActivitiesProperty(): int
    {
        return $this->monthSummary['total'];
    }

    public function getDefaultSelectedDateProperty(): ?string
    {
        $days = collect($this->currentMonthDays);

        if ($this->isViewingCurrentMonth()) {
            $today = $days->firstWhere('isToday', true);

            if ($today !== null) {
                return $today['date'];
            }
        }

        $firstWithActivity = $days->first(fn (array $day): bool => $day['totalCount'] > 0);

        return $firstWithActivity['date'] ?? $days->first()['date'] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function getWeekdayLabelsProperty(): array
    {
        return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    }

    /**
     * @return array<int, array{value: string, label: string, icon: string}>
     */
    public function getActivityFilterOptionsProperty(): array
    {
        return [
            ['value' => self::FILTER_ALL, 'label' => 'Todo', 'icon' => 'heroicon-m-squares-2x2'],
            ['value' => self::FILTER_EVENTS, 'label' => 'Eventos', 'icon' => 'heroicon-m-calendar'],
            ['value' => self::FILTER_PUBLICATIONS, 'label' => 'Publicaciones', 'icon' => 'heroicon-m-megaphone'],
        ];
    }

    public function formatMonthLabel(): string
    {
        return ucfirst(Carbon::parse($this->calendarMonth.'-01')->locale('es')->isoFormat('MMMM YYYY'));
    }

    /**
     * @param  array<string, mixed>  $day
     */
    public function formatDayHeading(array $day): string
    {
        return Carbon::parse($day['date'])->locale('es')->isoFormat('dddd D [de] MMMM');
    }

    /**
     * Etiqueta accesible de cada celda: fecha + carga de trabajo del día.
     *
     * @param  array<string, mixed>  $day
     */
    public function formatDayAriaLabel(array $day): string
    {
        $parts = [ucfirst($this->formatDayHeading($day))];

        if ($day['isToday']) {
            $parts[] = 'hoy';
        }

        if ($day['totalCount'] === 0) {
            $parts[] = 'sin actividad programada';
        } else {
            if ($day['eventsCount'] > 0) {
                $parts[] = $day['eventsCount'].' evento'.($day['eventsCount'] === 1 ? '' : 's');
            }

            if ($day['publicationsCount'] > 0) {
                $parts[] = $day['publicationsCount'].' publicaci'.($day['publicationsCount'] === 1 ? 'ón' : 'ones');
            }
        }

        return implode(', ', $parts);
    }

    public function formatBusiestDayLabel(): ?string
    {
        $date = $this->monthSummary['busiestDate'];

        if ($date === null) {
            return null;
        }

        return Carbon::parse($date)->locale('es')->isoFormat('D [de] MMMM');
    }

    public function editorialCalendarUrl(): string
    {
        return EditorialCalendar::getUrl();
    }

    public function corporateEventsCalendarUrl(): string
    {
        return CorporateEventsCalendar::getUrl();
    }

    public function createCorporateEventUrl(): string
    {
        return CorporateEventResource::getUrl('create');
    }

    public function createPublicationUrl(): string
    {
        return EditorialPublicationResource::getUrl('create');
    }

    public function canViewPublications(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasMarketingPermission(MarketingPermission::ViewCalendar);
    }

    public function canViewEvents(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasMarketingPermission(MarketingPermission::ViewCorporateEvents);
    }

    public function canManageEvents(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasMarketingPermission(MarketingPermission::ManageCorporateEvents);
    }

    public function canManagePublications(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasMarketingPermission(MarketingPermission::ManagePublications);
    }

    public function canFilterActivities(): bool
    {
        return $this->canViewEvents() && $this->canViewPublications();
    }

    public function showsEvents(): bool
    {
        return $this->canViewEvents() && $this->activityFilter !== self::FILTER_PUBLICATIONS;
    }

    public function showsPublications(): bool
    {
        return $this->canViewPublications() && $this->activityFilter !== self::FILTER_EVENTS;
    }
}
