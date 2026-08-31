<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CorporateEventsCalendar;
use App\Filament\Pages\EditorialCalendar;
use App\Marketing\MarketingPermission;
use App\Models\User;
use App\Services\Marketing\MarketingDashboardHeatmapService;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class MarketingActivityHeatmapWidget extends Widget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.marketing-activity-heatmap';

    public string $calendarMonth;

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
            includeEvents: $this->canViewEvents(),
            includePublications: $this->canViewPublications(),
        );
    }

    public function getMonthTotalActivitiesProperty(): int
    {
        return collect($this->heatmap['weeks'])
            ->flatten(1)
            ->filter(fn (array $day): bool => $day['isCurrentMonth'])
            ->sum('totalCount');
    }

    /**
     * @return array<int, string>
     */
    public function getWeekdayLabelsProperty(): array
    {
        return ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    }

    public function formatMonthLabel(): string
    {
        return Carbon::parse($this->calendarMonth.'-01')->locale('es')->isoFormat('MMMM YYYY');
    }

    public function formatDayHeading(array $day): string
    {
        return Carbon::parse($day['date'])->locale('es')->isoFormat('dddd D [de] MMMM');
    }

    public function editorialCalendarUrl(): string
    {
        return EditorialCalendar::getUrl();
    }

    public function corporateEventsCalendarUrl(): string
    {
        return CorporateEventsCalendar::getUrl();
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
}
