<?php

namespace App\Services\Marketing;

use App\Marketing\CorporateEventStatus;
use App\Marketing\PublicationStatus;
use App\Models\CorporateEvent;
use App\Models\EditorialPublication;
use Illuminate\Support\Carbon;

class MarketingDashboardHeatmapService
{
    /**
     * @return array{
     *     weeks: list<list<array{
     *         date: string,
     *         day: int,
     *         isCurrentMonth: bool,
     *         isToday: bool,
     *         eventsCount: int,
     *         publicationsCount: int,
     *         totalCount: int,
     *         intensity: int,
     *         plan: array{
     *             events: list<array{
     *                 time: string,
     *                 title: string,
     *                 type: ?string,
     *                 modality: ?string,
     *                 status: string,
     *                 statusColor: string,
     *                 venue: ?string,
     *             }>,
     *             publications: list<array{
     *                 time: string,
     *                 title: string,
     *                 platform: ?string,
     *                 platformIcon: ?string,
     *                 account: ?string,
     *                 status: string,
     *                 statusColor: string,
     *             }>,
     *         },
     *     }>>,
     *     maxTotal: int,
     * }
     */
    public function buildMonthGrid(
        string $calendarMonth,
        bool $includeEvents = true,
        bool $includePublications = true,
    ): array {
        $monthStart = Carbon::parse($calendarMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::MONDAY);

        $eventsByDate = $includeEvents
            ? $this->eventsForRange($gridStart, $gridEnd)
            : [];
        $publicationsByDate = $includePublications
            ? $this->publicationsForRange($gridStart, $gridEnd)
            : [];

        $weeks = [];
        $cursor = $gridStart->copy();
        $maxTotal = 0;

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $dateKey = $cursor->format('Y-m-d');
                $events = $eventsByDate[$dateKey] ?? [];
                $publications = $publicationsByDate[$dateKey] ?? [];
                $eventsCount = count($events);
                $publicationsCount = count($publications);
                $totalCount = $eventsCount + $publicationsCount;
                $maxTotal = max($maxTotal, $totalCount);

                $week[] = [
                    'date' => $dateKey,
                    'day' => $cursor->day,
                    'isCurrentMonth' => $cursor->month === $monthStart->month,
                    'isToday' => $cursor->isToday(),
                    'eventsCount' => $eventsCount,
                    'publicationsCount' => $publicationsCount,
                    'totalCount' => $totalCount,
                    'intensity' => $this->resolveIntensity($totalCount),
                    'plan' => [
                        'events' => $events,
                        'publications' => $publications,
                    ],
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [
            'weeks' => $weeks,
            'maxTotal' => $maxTotal,
        ];
    }

    public function resolveIntensity(int $totalCount): int
    {
        return match (true) {
            $totalCount <= 0 => 0,
            $totalCount === 1 => 1,
            $totalCount === 2 => 2,
            $totalCount === 3 => 3,
            default => 4,
        };
    }

    /**
     * @return array<string, list<array{
     *     time: string,
     *     title: string,
     *     type: ?string,
     *     modality: ?string,
     *     status: string,
     *     statusColor: string,
     *     venue: ?string,
     * }>>
     */
    private function eventsForRange(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $timezone = config('app.timezone');

        return CorporateEvent::query()
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd->copy()->endOfDay()])
            ->whereNot('status', CorporateEventStatus::Cancelled->value)
            ->orderBy('starts_at')
            ->get(['title', 'event_type', 'modality', 'status', 'starts_at', 'venue_name'])
            ->groupBy(fn (CorporateEvent $event): string => $event->starts_at
                ->timezone($timezone)
                ->format('Y-m-d'))
            ->map(fn ($events): array => $events
                ->map(function (CorporateEvent $event) use ($timezone): array {
                    $status = $event->statusEnum();

                    return [
                        'time' => $event->starts_at->timezone($timezone)->format('H:i'),
                        'title' => $event->title,
                        'type' => $event->typeEnum()?->getLabel(),
                        'modality' => $event->modalityEnum()?->getLabel(),
                        'status' => $status->getLabel(),
                        'statusColor' => $status->getColor(),
                        'venue' => $event->venue_name,
                    ];
                })
                ->values()
                ->all())
            ->all();
    }

    /**
     * @return array<string, list<array{
     *     time: string,
     *     title: string,
     *     platform: ?string,
     *     platformIcon: ?string,
     *     account: ?string,
     *     status: string,
     *     statusColor: string,
     * }>>
     */
    private function publicationsForRange(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $timezone = config('app.timezone');

        return EditorialPublication::query()
            ->with('socialAccount:id,name,platform')
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd->copy()->endOfDay()])
            ->whereNot('status', PublicationStatus::Cancelled->value)
            ->orderBy('scheduled_at')
            ->get(['title', 'status', 'scheduled_at', 'social_account_id'])
            ->groupBy(fn (EditorialPublication $publication): string => $publication->scheduled_at
                ->timezone($timezone)
                ->format('Y-m-d'))
            ->map(fn ($publications): array => $publications
                ->map(function (EditorialPublication $publication) use ($timezone): array {
                    $status = $publication->status;
                    $platform = $publication->socialAccount?->platform;

                    return [
                        'time' => $publication->scheduled_at->timezone($timezone)->format('H:i'),
                        'title' => $publication->title,
                        'platform' => $platform?->getLabel(),
                        'platformIcon' => $platform?->getImageUrl(),
                        'account' => $publication->socialAccount?->name,
                        'status' => $status->getLabel(),
                        'statusColor' => $status->getColor(),
                    ];
                })
                ->values()
                ->all())
            ->all();
    }
}
