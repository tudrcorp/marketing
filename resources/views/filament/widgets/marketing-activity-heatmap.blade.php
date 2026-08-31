<x-filament-widgets::widget>
    <section class="marketing-heatmap-widget" aria-label="Mapa de actividad mensual">
        <div class="marketing-heatmap-widget__glow" aria-hidden="true"></div>

        <header class="marketing-heatmap-widget__header">
            <div class="marketing-heatmap-widget__heading">
                <div class="marketing-heatmap-widget__icon-wrap">
                    <x-filament::icon icon="heroicon-o-chart-bar-square" class="marketing-heatmap-widget__icon" />
                </div>

                <div>
                    <div class="marketing-heatmap-widget__title-row">
                        <h3 class="marketing-heatmap-widget__title">Actividad del mes</h3>
                        @if ($this->monthTotalActivities > 0)
                            <span class="marketing-heatmap-widget__count">{{ $this->monthTotalActivities }}</span>
                        @endif
                    </div>
                    <p class="marketing-heatmap-widget__subtitle">
                        Eventos corporativos y publicaciones editoriales por día.
                        @if ($this->canViewEvents() && $this->canViewPublications())
                            Pasa el cursor sobre una fecha para ver la planificación completa.
                        @elseif ($this->canViewEvents())
                            Pasa el cursor sobre una fecha para ver los eventos programados.
                        @else
                            Pasa el cursor sobre una fecha para ver las publicaciones programadas.
                        @endif
                    </p>
                </div>
            </div>

            <div class="marketing-heatmap-widget__actions">
                @if ($this->canViewPublications())
                    <a href="{{ $this->editorialCalendarUrl() }}" class="marketing-heatmap-widget__link">
                        <x-filament::icon icon="heroicon-m-calendar-days" class="size-4" />
                        Editorial
                    </a>
                @endif

                @if ($this->canViewEvents())
                    <a href="{{ $this->corporateEventsCalendarUrl() }}" class="marketing-heatmap-widget__link marketing-heatmap-widget__link--primary">
                        <x-filament::icon icon="heroicon-m-calendar" class="size-4" />
                        Eventos
                    </a>
                @endif
            </div>
        </header>

        <div class="marketing-heatmap-widget__body">
            <div class="marketing-heatmap-widget__toolbar">
                <div class="marketing-heatmap-widget__nav">
                    <button type="button" wire:click="previousMonth" class="marketing-heatmap-widget__nav-btn" aria-label="Mes anterior">
                        <x-filament::icon icon="heroicon-m-chevron-left" class="size-4" />
                    </button>
                    <h4 class="marketing-heatmap-widget__month">{{ ucfirst($this->formatMonthLabel()) }}</h4>
                    <button type="button" wire:click="nextMonth" class="marketing-heatmap-widget__nav-btn" aria-label="Mes siguiente">
                        <x-filament::icon icon="heroicon-m-chevron-right" class="size-4" />
                    </button>
                </div>

                <button type="button" wire:click="goToToday" class="marketing-heatmap-widget__today-btn">
                    Hoy
                </button>
            </div>

            <div class="marketing-heatmap-widget__weekdays" role="row">
                @foreach ($this->weekdayLabels as $weekday)
                    <div class="marketing-heatmap-widget__weekday" role="columnheader">{{ $weekday }}</div>
                @endforeach
            </div>

            <div class="marketing-heatmap-widget__grid" role="grid">
                @foreach ($this->heatmap['weeks'] as $week)
                    <div class="marketing-heatmap-widget__week" role="row">
                        @foreach ($week as $dayIndex => $day)
                            <div
                                wire:key="heatmap-day-{{ $day['date'] }}"
                                tabindex="0"
                                @class([
                                    'marketing-heatmap-widget__cell',
                                    'marketing-heatmap-widget__cell--outside' => ! $day['isCurrentMonth'],
                                    'marketing-heatmap-widget__cell--today' => $day['isToday'],
                                    'marketing-heatmap-widget__cell--intensity-' . $day['intensity'],
                                ])
                                role="gridcell"
                                aria-label="{{ $this->formatDayHeading($day) }}"
                            >
                                <span class="marketing-heatmap-widget__day-number">{{ $day['day'] }}</span>

                                @if ($day['isCurrentMonth'])
                                    <div
                                        @class([
                                            'marketing-heatmap-widget__popover',
                                            'marketing-heatmap-widget__popover--start' => $dayIndex === 0,
                                            'marketing-heatmap-widget__popover--end' => $dayIndex === 6,
                                        ])
                                        role="tooltip"
                                    >
                                        <div class="marketing-heatmap-widget__popover-surface">
                                            <header class="marketing-heatmap-widget__popover-header">
                                                <p class="marketing-heatmap-widget__popover-date">{{ ucfirst($this->formatDayHeading($day)) }}</p>
                                                @if ($day['totalCount'] > 0)
                                                    <span class="marketing-heatmap-widget__popover-summary">
                                                        {{ $day['totalCount'] }} actividad{{ $day['totalCount'] === 1 ? '' : 'es' }}
                                                    </span>
                                                @endif
                                            </header>

                                            <div class="marketing-heatmap-widget__popover-body">
                                                @if ($day['totalCount'] === 0)
                                                    <p class="marketing-heatmap-widget__popover-empty">Sin actividad programada.</p>
                                                @else
                                                    @if ($this->canViewEvents() && count($day['plan']['events']) > 0)
                                                        <section class="marketing-heatmap-widget__popover-section">
                                                            <h5 class="marketing-heatmap-widget__popover-section-title">
                                                                <x-filament::icon icon="heroicon-m-calendar" class="size-3.5" />
                                                                Eventos
                                                            </h5>
                                                            <ul class="marketing-heatmap-widget__popover-list">
                                                                @foreach ($day['plan']['events'] as $event)
                                                                    <li class="marketing-heatmap-widget__popover-item">
                                                                        <div class="marketing-heatmap-widget__popover-item-top">
                                                                            <span class="marketing-heatmap-widget__popover-time">{{ $event['time'] }}</span>
                                                                            <span @class([
                                                                                'marketing-heatmap-widget__popover-badge',
                                                                                'marketing-heatmap-widget__popover-badge--' . $event['statusColor'],
                                                                            ])>{{ $event['status'] }}</span>
                                                                        </div>
                                                                        <p class="marketing-heatmap-widget__popover-item-title">{{ $event['title'] }}</p>
                                                                        @if ($event['type'] || $event['modality'])
                                                                            <p class="marketing-heatmap-widget__popover-item-meta">
                                                                                {{ collect([$event['type'], $event['modality']])->filter()->implode(' · ') }}
                                                                            </p>
                                                                        @endif
                                                                        @if ($event['venue'])
                                                                            <p class="marketing-heatmap-widget__popover-item-venue">
                                                                                <x-filament::icon icon="heroicon-m-map-pin" class="size-3.5" />
                                                                                {{ $event['venue'] }}
                                                                            </p>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </section>
                                                    @endif

                                                    @if ($this->canViewPublications() && count($day['plan']['publications']) > 0)
                                                        <section class="marketing-heatmap-widget__popover-section">
                                                            <h5 class="marketing-heatmap-widget__popover-section-title">
                                                                <x-filament::icon icon="heroicon-m-megaphone" class="size-3.5" />
                                                                Publicaciones
                                                            </h5>
                                                            <ul class="marketing-heatmap-widget__popover-list">
                                                                @foreach ($day['plan']['publications'] as $publication)
                                                                    <li class="marketing-heatmap-widget__popover-item">
                                                                        <div class="marketing-heatmap-widget__popover-item-top">
                                                                            <span class="marketing-heatmap-widget__popover-time">{{ $publication['time'] }}</span>
                                                                            <span @class([
                                                                                'marketing-heatmap-widget__popover-badge',
                                                                                'marketing-heatmap-widget__popover-badge--' . $publication['statusColor'],
                                                                            ])>{{ $publication['status'] }}</span>
                                                                        </div>
                                                                        <p class="marketing-heatmap-widget__popover-item-title">{{ $publication['title'] }}</p>
                                                                        @if ($publication['platform'] || $publication['account'])
                                                                            <p class="marketing-heatmap-widget__popover-item-meta">
                                                                                @if ($publication['platformIcon'])
                                                                                    <img
                                                                                        src="{{ $publication['platformIcon'] }}"
                                                                                        alt="{{ $publication['platform'] }}"
                                                                                        class="marketing-platform-option__icon"
                                                                                        width="16"
                                                                                        height="16"
                                                                                        loading="lazy"
                                                                                    />
                                                                                @endif
                                                                                <span>
                                                                                    {{ collect([$publication['platform'], $publication['account']])->filter()->implode(' · ') }}
                                                                                </span>
                                                                            </p>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </section>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <footer class="marketing-heatmap-widget__legend" aria-label="Intensidad de actividad">
                <span class="marketing-heatmap-widget__legend-label">Menos</span>
                <div class="marketing-heatmap-widget__legend-scale">
                    @foreach (range(0, 4) as $level)
                        <span
                            @class([
                                'marketing-heatmap-widget__legend-cell',
                                'marketing-heatmap-widget__cell--intensity-' . $level,
                            ])
                            aria-hidden="true"
                        ></span>
                    @endforeach
                </div>
                <span class="marketing-heatmap-widget__legend-label">Más</span>
            </footer>
        </div>
    </section>
</x-filament-widgets::widget>
