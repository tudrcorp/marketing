@php
    $summary = $this->monthSummary;
    $showsEvents = $this->showsEvents();
    $showsPublications = $this->showsPublications();
    $weekdayFullLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $intensityLabels = ['Sin actividad', '1 actividad', '2 actividades', '3 actividades', '4 o más actividades'];
@endphp

<x-filament-widgets::widget>
    <section class="marketing-heatmap-widget" aria-label="Mapa de actividad mensual" data-heatmap-root>
        <div class="marketing-heatmap-widget__glow" aria-hidden="true"></div>
        <div class="marketing-heatmap-widget__glow marketing-heatmap-widget__glow--secondary" aria-hidden="true"></div>

        <header class="marketing-heatmap-widget__header">
            <div class="marketing-heatmap-widget__heading">
                <div class="marketing-heatmap-widget__icon-wrap">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="marketing-heatmap-widget__icon" />
                </div>

                <div class="min-w-0">
                    <div class="marketing-heatmap-widget__title-row">
                        <h3 class="marketing-heatmap-widget__title">Actividad del mes</h3>

                        @if ($summary['total'] > 0)
                            <span class="marketing-heatmap-widget__count">
                                {{ $summary['total'] }} {{ $summary['total'] === 1 ? 'actividad' : 'actividades' }}
                            </span>
                        @endif
                    </div>

                    <p class="marketing-heatmap-widget__subtitle">
                        Eventos corporativos y publicaciones editoriales por día. Pasa el cursor o selecciona una fecha
                        para ver su planificación; con el teclado usa las flechas para recorrer el mes.
                    </p>
                </div>
            </div>

            <div class="marketing-heatmap-widget__actions">
                @if ($this->canViewPublications())
                    <a href="{{ $this->editorialCalendarUrl() }}" class="marketing-heatmap-widget__link" wire:navigate>
                        <x-filament::icon icon="heroicon-m-megaphone" class="size-4" />
                        Editorial
                    </a>
                @endif

                @if ($this->canViewEvents())
                    <a href="{{ $this->corporateEventsCalendarUrl() }}" class="marketing-heatmap-widget__link marketing-heatmap-widget__link--primary" wire:navigate>
                        <x-filament::icon icon="heroicon-m-calendar" class="size-4" />
                        Eventos
                    </a>
                @endif
            </div>
        </header>

        <div class="marketing-heatmap-widget__stats" role="list">
            @if ($showsEvents)
                <div class="marketing-heatmap-widget__stat" role="listitem">
                    <span class="marketing-heatmap-widget__stat-dot marketing-heatmap-widget__stat-dot--events" aria-hidden="true"></span>
                    <span class="marketing-heatmap-widget__stat-value">{{ $summary['events'] }}</span>
                    <span class="marketing-heatmap-widget__stat-label">{{ $summary['events'] === 1 ? 'evento' : 'eventos' }}</span>
                </div>
            @endif

            @if ($showsPublications)
                <div class="marketing-heatmap-widget__stat" role="listitem">
                    <span class="marketing-heatmap-widget__stat-dot marketing-heatmap-widget__stat-dot--publications" aria-hidden="true"></span>
                    <span class="marketing-heatmap-widget__stat-value">{{ $summary['publications'] }}</span>
                    <span class="marketing-heatmap-widget__stat-label">{{ $summary['publications'] === 1 ? 'publicación' : 'publicaciones' }}</span>
                </div>
            @endif

            <div class="marketing-heatmap-widget__stat" role="listitem">
                <x-filament::icon icon="heroicon-m-calendar-days" class="marketing-heatmap-widget__stat-icon" />
                <span class="marketing-heatmap-widget__stat-value">{{ $summary['activeDays'] }}</span>
                <span class="marketing-heatmap-widget__stat-label">{{ $summary['activeDays'] === 1 ? 'día con agenda' : 'días con agenda' }}</span>
            </div>

            @if ($summary['busiestDate'] !== null)
                <button
                    type="button"
                    class="marketing-heatmap-widget__stat marketing-heatmap-widget__stat--action"
                    data-heatmap-goto="{{ $summary['busiestDate'] }}"
                    title="Ir al día con más carga del mes"
                >
                    <x-filament::icon icon="heroicon-m-bolt" class="marketing-heatmap-widget__stat-icon" />
                    <span class="marketing-heatmap-widget__stat-label">Pico:</span>
                    <span class="marketing-heatmap-widget__stat-value">{{ $this->formatBusiestDayLabel() }}</span>
                    <span class="marketing-heatmap-widget__stat-label">· {{ $summary['busiestCount'] }}</span>
                </button>
            @endif
        </div>

        <div class="marketing-heatmap-widget__body">
            <div class="marketing-heatmap-widget__toolbar">
                <div class="marketing-heatmap-widget__nav">
                    <button
                        type="button"
                        wire:click="previousMonth"
                        class="marketing-heatmap-widget__nav-btn"
                        aria-label="Mes anterior"
                        title="Mes anterior"
                    >
                        <x-filament::icon icon="heroicon-m-chevron-left" class="size-4" />
                    </button>

                    <h4 class="marketing-heatmap-widget__month" aria-live="polite">{{ $this->formatMonthLabel() }}</h4>

                    <button
                        type="button"
                        wire:click="nextMonth"
                        class="marketing-heatmap-widget__nav-btn"
                        aria-label="Mes siguiente"
                        title="Mes siguiente"
                    >
                        <x-filament::icon icon="heroicon-m-chevron-right" class="size-4" />
                    </button>

                    @unless ($this->isViewingCurrentMonth())
                        <button
                            type="button"
                            wire:click="goToToday"
                            class="marketing-heatmap-widget__today-btn"
                            title="Volver al mes actual"
                        >
                            <x-filament::icon icon="heroicon-m-arrow-uturn-left" class="size-3.5" />
                            Hoy
                        </button>
                    @endunless
                </div>

                @if ($this->canFilterActivities())
                    <div class="marketing-heatmap-widget__filters" role="group" aria-label="Filtrar actividad">
                        @foreach ($this->activityFilterOptions as $option)
                            <button
                                type="button"
                                wire:click="setActivityFilter('{{ $option['value'] }}')"
                                @class([
                                    'marketing-heatmap-widget__filter',
                                    'marketing-heatmap-widget__filter--active' => $this->activityFilter === $option['value'],
                                ])
                                aria-pressed="{{ $this->activityFilter === $option['value'] ? 'true' : 'false' }}"
                            >
                                <x-filament::icon :icon="$option['icon']" class="size-3.5" />
                                {{ $option['label'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="marketing-heatmap-widget__calendar" wire:loading.class="marketing-heatmap-widget__calendar--busy">
                <div class="marketing-heatmap-widget__spinner" wire:loading.delay.shortest aria-hidden="true">
                    <x-filament::loading-indicator class="size-6" />
                </div>

                <div class="marketing-heatmap-widget__weekdays" role="row">
                    @foreach ($this->weekdayLabels as $weekdayIndex => $weekday)
                        <div
                            @class([
                                'marketing-heatmap-widget__weekday',
                                'marketing-heatmap-widget__weekday--weekend' => $weekdayIndex >= 5,
                            ])
                            role="columnheader"
                            title="{{ $weekdayFullLabels[$weekdayIndex] }}"
                        >{{ $weekday }}</div>
                    @endforeach
                </div>

                <div
                    class="marketing-heatmap-widget__grid"
                    role="grid"
                    aria-label="{{ $this->formatMonthLabel() }}"
                    data-heatmap-grid
                    data-default-date="{{ $this->defaultSelectedDate }}"
                >
                    @php($cellIndex = 0)

                    @foreach ($this->visibleWeeks as $week)
                        <div class="marketing-heatmap-widget__week" role="row">
                            @foreach ($week as $weekdayIndex => $day)
                                @if (! $day['isCurrentMonth'])
                                    <div
                                        wire:key="heatmap-day-{{ $day['date'] }}"
                                        class="marketing-heatmap-widget__cell marketing-heatmap-widget__cell--outside"
                                        role="gridcell"
                                        aria-hidden="true"
                                    >
                                        <span class="marketing-heatmap-widget__day-number">{{ $day['day'] }}</span>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        wire:key="heatmap-day-{{ $day['date'] }}"
                                        data-date="{{ $day['date'] }}"
                                        style="--cell-index: {{ $cellIndex }}"
                                        @class([
                                            'marketing-heatmap-widget__cell',
                                            'marketing-heatmap-widget__cell--today' => $day['isToday'],
                                            'marketing-heatmap-widget__cell--weekend' => $weekdayIndex >= 5,
                                            'marketing-heatmap-widget__cell--empty' => $day['totalCount'] === 0,
                                            'marketing-heatmap-widget__cell--intensity-'.$day['intensity'],
                                        ])
                                        tabindex="-1"
                                        aria-pressed="false"
                                        @if ($day['isToday']) aria-current="date" @endif
                                        role="gridcell"
                                        aria-label="{{ $this->formatDayAriaLabel($day) }}"
                                    >
                                        <span class="marketing-heatmap-widget__day-number">{{ $day['day'] }}</span>

                                        @if ($day['totalCount'] > 0)
                                            <span class="marketing-heatmap-widget__markers" aria-hidden="true">
                                                @if ($day['eventsCount'] > 0)
                                                    <span class="marketing-heatmap-widget__marker marketing-heatmap-widget__marker--events">
                                                        <span class="marketing-heatmap-widget__marker-dot"></span>{{ $day['eventsCount'] }}
                                                    </span>
                                                @endif

                                                @if ($day['publicationsCount'] > 0)
                                                    <span class="marketing-heatmap-widget__marker marketing-heatmap-widget__marker--publications">
                                                        <span class="marketing-heatmap-widget__marker-dot"></span>{{ $day['publicationsCount'] }}
                                                    </span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="marketing-heatmap-widget__markers marketing-heatmap-widget__markers--empty" aria-hidden="true"></span>
                                        @endif
                                    </button>
                                @endif

                                @php($cellIndex += $day['isCurrentMonth'] ? 1 : 0)
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="marketing-heatmap-widget__legend-row">
                <div class="marketing-heatmap-widget__keys">
                    @if ($showsEvents)
                        <span class="marketing-heatmap-widget__key">
                            <span class="marketing-heatmap-widget__stat-dot marketing-heatmap-widget__stat-dot--events" aria-hidden="true"></span>
                            Eventos
                        </span>
                    @endif

                    @if ($showsPublications)
                        <span class="marketing-heatmap-widget__key">
                            <span class="marketing-heatmap-widget__stat-dot marketing-heatmap-widget__stat-dot--publications" aria-hidden="true"></span>
                            Publicaciones
                        </span>
                    @endif
                </div>

                <div class="marketing-heatmap-widget__legend" aria-label="Intensidad de actividad">
                    <span class="marketing-heatmap-widget__legend-label">Menos</span>
                    <div class="marketing-heatmap-widget__legend-scale">
                        @foreach (range(0, 4) as $level)
                            <span
                                @class([
                                    'marketing-heatmap-widget__legend-cell',
                                    'marketing-heatmap-widget__cell--intensity-'.$level,
                                ])
                                title="{{ $intensityLabels[$level] }}"
                            ></span>
                        @endforeach
                    </div>
                    <span class="marketing-heatmap-widget__legend-label">Más</span>
                </div>
            </div>
        </div>

        <div class="marketing-heatmap-widget__details" data-heatmap-details aria-live="polite">
            @foreach ($this->currentMonthDays as $day)
                @include('filament.widgets.partials.heatmap-day-detail', ['day' => $day])
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>

@script
<script>
    const root = $el.querySelector('[data-heatmap-root]') ?? $el

    let selected = null
    let hovered = null

    const cells = () => Array.from(root.querySelectorAll('[data-date]'))
    const dates = () => cells().map((cell) => cell.dataset.date)

    const activeDate = () => hovered ?? selected

    const paintPanels = () => {
        const active = activeDate()
        const isPreview = hovered !== null && hovered !== selected

        root.querySelectorAll('[data-heatmap-panel]').forEach((panel) => {
            const matches = panel.dataset.heatmapPanel === active

            if (matches && panel.hidden) {
                panel.hidden = false
                panel.classList.remove('marketing-heatmap-widget__detail--in')
                void panel.offsetWidth
                panel.classList.add('marketing-heatmap-widget__detail--in')
            } else if (! matches) {
                panel.hidden = true
            }

            const chip = panel.querySelector('[data-heatmap-preview-chip]')

            if (chip) {
                chip.hidden = ! (matches && isPreview)
            }
        })
    }

    const paintCells = () => {
        cells().forEach((cell) => {
            const isSelected = cell.dataset.date === selected

            cell.classList.toggle('marketing-heatmap-widget__cell--selected', isSelected)
            cell.classList.toggle('marketing-heatmap-widget__cell--previewing', cell.dataset.date === hovered)
            cell.setAttribute('tabindex', isSelected ? '0' : '-1')
            cell.setAttribute('aria-pressed', isSelected ? 'true' : 'false')
        })
    }

    const render = () => {
        paintCells()
        paintPanels()
    }

    const sync = () => {
        const grid = root.querySelector('[data-heatmap-grid]')
        const fallback = grid?.dataset.defaultDate || null
        const available = dates()

        hovered = null

        if (! selected || ! available.includes(selected)) {
            selected = available.includes(fallback) ? fallback : (available[0] ?? null)
        }

        render()
    }

    const focusDate = (date) => {
        root.querySelector(`[data-date="${date}"]`)?.focus({ preventScroll: true })
    }

    const moveSelection = (from, delta) => {
        const available = dates()
        const index = available.indexOf(from)

        if (index === -1) {
            return
        }

        const next = available[Math.min(Math.max(index + delta, 0), available.length - 1)]

        selected = next
        render()
        focusDate(next)
    }

    root.addEventListener('click', (event) => {
        const shortcut = event.target.closest('[data-heatmap-goto]')

        if (shortcut) {
            selected = shortcut.dataset.heatmapGoto
            hovered = null
            render()
            focusDate(selected)

            return
        }

        const cell = event.target.closest('[data-date]')

        if (cell) {
            selected = cell.dataset.date
            render()
        }
    })

    root.addEventListener('mouseover', (event) => {
        const cell = event.target.closest('[data-date]')
        const next = cell ? cell.dataset.date : null

        if (next !== hovered) {
            hovered = next
            render()
        }
    })

    root.addEventListener('mouseleave', () => {
        if (hovered !== null) {
            hovered = null
            render()
        }
    })

    root.addEventListener('focusin', (event) => {
        const cell = event.target.closest('[data-date]')

        if (cell && cell.dataset.date !== hovered) {
            hovered = cell.dataset.date
            render()
        }
    })

    root.addEventListener('focusout', (event) => {
        if (event.target.closest('[data-date]') && hovered !== null) {
            hovered = null
            render()
        }
    })

    root.addEventListener('keydown', (event) => {
        const cell = event.target.closest('[data-date]')

        if (! cell) {
            return
        }

        const steps = {
            ArrowRight: 1,
            ArrowLeft: -1,
            ArrowDown: 7,
            ArrowUp: -7,
        }

        if (event.key in steps) {
            event.preventDefault()
            moveSelection(cell.dataset.date, steps[event.key])

            return
        }

        if (event.key === 'Home' || event.key === 'End') {
            event.preventDefault()
            moveSelection(cell.dataset.date, event.key === 'Home' ? -dates().length : dates().length)

            return
        }

        if (event.key === 'PageUp' || event.key === 'PageDown') {
            event.preventDefault()
            event.key === 'PageUp' ? $wire.previousMonth() : $wire.nextMonth()
        }
    })

    $wire.on('heatmap-refreshed', () => requestAnimationFrame(sync))

    sync()
</script>
@endscript
