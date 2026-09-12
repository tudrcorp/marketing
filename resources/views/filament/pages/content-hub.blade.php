<x-filament-panels::page class="content-hub">
    @assets
        @vite('resources/js/content-hub.js')
    @endassets

    @php
        $canManage = $this->canManagePosts();
        $summary = $this->summary;
    @endphp

    {{-- Selector de modo y marca activa --------------------------------------------- --}}
    <section class="content-masthead">
        <div class="content-masthead__modes" role="group" aria-label="Selector de modo">
            <button
                type="button"
                wire:click="setMode('focus')"
                @class(['content-mode', 'content-mode--active' => $this->mode === 'focus'])
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 4.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM3 10a7 7 0 1 1 14 0 7 7 0 0 1-14 0Zm7-2.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" clip-rule="evenodd" />
                </svg>
                Modo Enfoque
            </button>
            <button
                type="button"
                wire:click="setMode('master')"
                @class(['content-mode', 'content-mode--active' => $this->mode === 'master'])
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M3 3.5A1.5 1.5 0 0 1 4.5 2h3A1.5 1.5 0 0 1 9 3.5v3A1.5 1.5 0 0 1 7.5 8h-3A1.5 1.5 0 0 1 3 6.5v-3Zm8 0A1.5 1.5 0 0 1 12.5 2h3A1.5 1.5 0 0 1 17 3.5v3A1.5 1.5 0 0 1 15.5 8h-3A1.5 1.5 0 0 1 11 6.5v-3ZM3 13.5A1.5 1.5 0 0 1 4.5 12h3A1.5 1.5 0 0 1 9 13.5v3A1.5 1.5 0 0 1 7.5 18h-3A1.5 1.5 0 0 1 3 16.5v-3Zm8 0a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a1.5 1.5 0 0 1-1.5-1.5v-3Z" />
                </svg>
                Modo Máster
            </button>
        </div>

        <div class="content-masthead__brands" role="group" aria-label="Marcas">
            @forelse ($this->brands as $brand)
                <button
                    type="button"
                    wire:click="selectBrand({{ $brand->id }})"
                    @class([
                        'content-brand-pill',
                        'content-brand-pill--active' => $this->mode === 'focus' && $this->brandId === $brand->id,
                    ])
                    style="--brand-color: {{ $brand->color_hex }}"
                    title="{{ $brand->brand_voice }}"
                >
                    <span class="content-brand-pill__avatar">{{ $brand->initials() }}</span>
                    <span class="content-brand-pill__name">{{ $brand->name }}</span>
                </button>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Aún no hay marcas registradas. Crea la primera desde <strong>Marcas</strong>.
                </p>
            @endforelse
        </div>

        @if ($canManage)
            <div class="content-masthead__actions">
                {{ $this->createPostAction }}
            </div>
        @endif
    </section>

    {{-- Resumen --------------------------------------------------------------------- --}}
    <section class="content-summary" aria-label="Resumen de la selección">
        <div class="content-summary__item">
            <span class="content-summary__value">{{ $summary['total'] }}</span>
            <span class="content-summary__label">Piezas</span>
        </div>
        <div class="content-summary__item content-summary__item--warning">
            <span class="content-summary__value">{{ $summary['urgent'] }}</span>
            <span class="content-summary__label">Prioridad alta</span>
        </div>
        <div class="content-summary__item content-summary__item--danger">
            <span class="content-summary__value">{{ $summary['blocked'] }}</span>
            <span class="content-summary__label">Bloqueadas</span>
        </div>
        <div class="content-summary__item content-summary__item--info">
            <span class="content-summary__value">{{ $summary['scheduled'] }}</span>
            <span class="content-summary__label">Programadas</span>
        </div>
        <div class="content-summary__item content-summary__item--danger">
            <span class="content-summary__value">{{ $summary['overdue'] }}</span>
            <span class="content-summary__label">Vencidas</span>
        </div>
    </section>

    {{-- Barra de vistas y filtros ---------------------------------------------------- --}}
    <section class="content-toolbar">
        <div class="content-toolbar__views" role="group" aria-label="Vista del tablero">
            <button
                type="button"
                wire:click="setBoardView('kanban')"
                @class(['content-toggle', 'content-toggle--active' => $this->boardView === 'kanban'])
            >
                Kanban
            </button>
            <button
                type="button"
                wire:click="setBoardView('calendar')"
                @class(['content-toggle', 'content-toggle--active' => $this->boardView === 'calendar'])
            >
                Calendario
            </button>
        </div>

        @if ($this->boardView === 'calendar')
            <div class="content-toolbar__period">
                <div class="content-toolbar__views" role="group" aria-label="Rango del calendario">
                    <button
                        type="button"
                        wire:click="setCalendarView('month')"
                        @class(['content-toggle', 'content-toggle--active' => $this->calendarView === 'month'])
                    >
                        Vista mensual
                    </button>
                    <button
                        type="button"
                        wire:click="setCalendarView('week')"
                        @class(['content-toggle', 'content-toggle--active' => $this->calendarView === 'week'])
                    >
                        Vista semanal
                    </button>
                </div>

                <div class="content-toolbar__nav">
                    <button type="button" wire:click="previousPeriod" class="content-nav-button" aria-label="Periodo anterior">‹</button>
                    <span class="content-toolbar__label">{{ $this->formatPeriodLabel() }}</span>
                    <button type="button" wire:click="nextPeriod" class="content-nav-button" aria-label="Periodo siguiente">›</button>
                    <button type="button" wire:click="goToToday" class="content-nav-today">Hoy</button>
                </div>
            </div>
        @endif

        <div class="content-toolbar__search">
            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="Buscar por título o copy…"
                class="content-input"
                aria-label="Buscar piezas"
            />
        </div>
    </section>

    {{-- Filtro por batching (trabajo por lotes) -------------------------------------- --}}
    <section class="content-filters" aria-label="Filtros">
        <div class="content-filters__batching">
            <span class="content-filters__legend">Trabajo por lotes</span>
            <div class="content-filters__chips">
                <button
                    type="button"
                    wire:click="$set('batching', null)"
                    @class(['content-chip-button', 'content-chip-button--active' => $this->batching === null])
                >
                    Todo
                </button>
                @foreach ($this->batchingOptions as $option)
                    <button
                        type="button"
                        wire:click="$set('batching', '{{ $option['value'] }}')"
                        @class(['content-chip-button', 'content-chip-button--active' => $this->batching === $option['value']])
                    >
                        {{ $option['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="content-filters__selects">
            <select wire:model.live="priority" class="content-input" aria-label="Prioridad">
                <option value="">Toda prioridad</option>
                @foreach ($this->priorityOptions as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>

            <select wire:model.live="format" class="content-input" aria-label="Formato">
                <option value="">Todo formato</option>
                @foreach ($this->formatOptions as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>

            <select wire:model.live="pillar" class="content-input" aria-label="Pilar de contenido">
                <option value="">Todo pilar</option>
                @foreach ($this->pillarOptions as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>

            <select wire:model.live="blocker" class="content-input" aria-label="Tipo de bloqueo">
                <option value="">Todo bloqueo</option>
                @foreach ($this->blockerOptions as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="toggleOnlyBlocked"
                @class(['content-chip-button content-chip-button--danger', 'content-chip-button--active' => $this->onlyBlocked])
            >
                Solo bloqueadas
            </button>

            @if ($this->hasActiveFilters())
                <button type="button" wire:click="resetFilters" class="content-reset">Limpiar filtros</button>
            @endif
        </div>
    </section>

    {{-- Visor de bloqueos ------------------------------------------------------------ --}}
    @if (filled($this->blockerGroups))
        <section class="content-blockers" aria-label="Visor de bloqueos">
            <h2 class="content-blockers__title">Cuellos de botella</h2>
            <div class="content-blockers__groups">
                @foreach ($this->blockerGroups as $group)
                    <div class="content-blockers__group" style="--blocker-color: {{ $group['blocker']->getHexColor() }}">
                        <header class="content-blockers__header">
                            <span class="content-blockers__label">{{ $group['blocker']->getLabel() }}</span>
                            <span class="content-blockers__count">{{ $group['count'] }}</span>
                        </header>
                        <ul class="content-blockers__list">
                            @foreach ($group['posts']->take(4) as $post)
                                <li>
                                    <button
                                        type="button"
                                        wire:click="mountAction('editPost', { post: {{ $post->id }} })"
                                        class="content-blockers__item"
                                    >
                                        <span class="content-blockers__dot" style="background-color: {{ $post->brand?->color_hex }}"></span>
                                        <span class="truncate">{{ $post->title }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        @if ($group['count'] > 4)
                            <p class="content-blockers__more">+{{ $group['count'] - 4 }} piezas más</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Tablero / Calendario --------------------------------------------------------- --}}
    @if ($this->boardView === 'kanban')
        @include('filament.pages.partials.content-hub.kanban', [
            'columns' => $this->kanbanColumns,
            'canManage' => $canManage,
        ])
    @else
        <section class="content-calendar">
            <div class="content-calendar__main">
                <div class="content-calendar__legend" aria-label="Semáforo de carga laboral">
                    <span class="content-calendar__legend-title">Semáforo de carga</span>
                    @foreach ($this->workloadLegend as $item)
                        <span class="content-calendar__legend-item">
                            <span class="content-calendar__legend-dot workload-{{ $item['level'] }}"></span>
                            {{ $item['label'] }}
                            <span class="content-calendar__legend-hint">({{ $item['description'] }})</span>
                        </span>
                    @endforeach
                </div>

                <div class="content-calendar__weekdays" aria-hidden="true">
                    @foreach ($this->weekdayLabels as $label)
                        <span>{{ $label }}</span>
                    @endforeach
                </div>

                @if ($this->calendarView === 'month')
                    <div class="content-calendar__grid">
                        @foreach ($this->monthWeeks as $weekIndex => $week)
                            @foreach ($week as $day)
                                <div wire:key="month-{{ $day['date'] }}">
                                    @include('filament.pages.partials.content-hub.calendar-day', [
                                        'day' => $day,
                                        'canManage' => $canManage,
                                    ])
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <div class="content-calendar__grid content-calendar__grid--week">
                        @foreach ($this->weekDays as $day)
                            <div wire:key="week-{{ $day['date'] }}">
                                @include('filament.pages.partials.content-hub.calendar-day', [
                                    'day' => $day,
                                    'canManage' => $canManage,
                                    'expanded' => true,
                                ])
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="content-calendar__aside" aria-label="Detalle del día">
                <header class="content-calendar__aside-header">
                    <h2>{{ $this->formatDayLabel($this->selectedDate) }}</h2>
                    <p>{{ $this->selectedDayPosts->count() }} entrega(s) programada(s)</p>
                </header>

                <div class="content-calendar__aside-list">
                    @forelse ($this->selectedDayPosts as $post)
                        <div wire:key="day-post-{{ $post->id }}">
                            @include('filament.pages.partials.content-hub.post-card', [
                                'post' => $post,
                                'canManage' => $canManage,
                                'draggable' => $canManage,
                                'compact' => true,
                            ])
                        </div>
                    @empty
                        <p class="content-calendar__aside-empty">
                            Sin entregas este día.
                            @if ($canManage)
                                Arrastra una pieza al calendario o
                                <button
                                    type="button"
                                    wire:click="mountAction('createPost', { date: '{{ $this->selectedDate }}' })"
                                    class="content-link"
                                >crea una nueva</button>.
                            @endif
                        </p>
                    @endforelse
                </div>
            </aside>
        </section>
    @endif

    {{-- Matriz de balance de pilares -------------------------------------------------- --}}
    <section class="content-pillars" aria-label="Matriz de balance de pilares">
        <header class="content-pillars__header">
            <h2>Balance de pilares</h2>
            <p>
                {{ $this->mode === 'focus' && $this->activeBrand
                    ? 'Distribución de contenido de '.$this->activeBrand->name
                    : 'Distribución global de todas las marcas' }}
            </p>
        </header>

        <div class="content-pillars__bars">
            @foreach ($this->pillarBalance as $item)
                <div class="content-pillars__row">
                    <span class="content-pillars__label">{{ $item['label'] }}</span>
                    <span class="content-pillars__track">
                        <span
                            class="content-pillars__fill"
                            style="width: {{ max($item['percentage'], 1.5) }}%; background-color: {{ $item['color'] }}"
                        ></span>
                    </span>
                    <span class="content-pillars__value">{{ $item['percentage'] }}% <span class="content-pillars__count">({{ $item['count'] }})</span></span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Cola de urgencia del Modo Máster ---------------------------------------------- --}}
    @if ($this->mode === 'master' && $this->boardView === 'kanban')
        <section class="content-urgent" aria-label="Cola global por urgencia">
            <header class="content-pillars__header">
                <h2>Prioridad global</h2>
                <p>Piezas abiertas de todas las marcas, ordenadas por urgencia y fecha.</p>
            </header>

            <div class="content-urgent__grid">
                @foreach ($this->urgentQueue->take(8) as $post)
                    <div wire:key="urgent-{{ $post->id }}">
                        @include('filament.pages.partials.content-hub.post-card', [
                            'post' => $post,
                            'canManage' => $canManage,
                            'compact' => true,
                        ])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
