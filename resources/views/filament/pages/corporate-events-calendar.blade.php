<x-filament-panels::page class="marketing-agenda marketing-events-agenda">
    <div class="marketing-agenda__shell">
        <aside class="marketing-agenda__filters" aria-label="Filtros del calendario">
            <label class="marketing-agenda__filter">
                <span>Tipo de evento</span>
                <select wire:model.live="eventType" class="marketing-agenda__select">
                    <option value="">Todos los tipos</option>
                    @foreach ($this->eventTypeFilterOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <label class="marketing-agenda__filter">
                <span>Modalidad</span>
                <select wire:model.live="modality" class="marketing-agenda__select">
                    <option value="">Todas las modalidades</option>
                    @foreach ($this->modalityFilterOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
        </aside>

        <div class="marketing-agenda__layout">
            <section class="marketing-agenda__calendar" aria-label="Calendario mensual de eventos">
                <header class="marketing-agenda__calendar-header">
                    <div class="marketing-agenda__calendar-toolbar">
                        <div class="marketing-agenda__calendar-nav">
                            <button type="button" wire:click="previousMonth" class="marketing-agenda__nav-btn" aria-label="Mes anterior">
                                <x-filament::icon icon="heroicon-m-chevron-left" class="size-4" />
                            </button>
                            <h2 class="marketing-agenda__month-title">{{ ucfirst($this->formatMonthLabel()) }}</h2>
                            <button type="button" wire:click="nextMonth" class="marketing-agenda__nav-btn" aria-label="Mes siguiente">
                                <x-filament::icon icon="heroicon-m-chevron-right" class="size-4" />
                            </button>
                        </div>

                        <button type="button" wire:click="goToToday" class="marketing-agenda__today-btn">
                            Hoy
                        </button>
                    </div>
                </header>

                <div class="marketing-agenda__weekdays" role="row">
                    @foreach ($this->weekdayLabels as $weekday)
                        <div class="marketing-agenda__weekday" role="columnheader">{{ $weekday }}</div>
                    @endforeach
                </div>

                <div class="marketing-agenda__grid" role="grid">
                    @foreach ($this->calendarWeeks as $week)
                        <div class="marketing-agenda__week" role="row">
                            @foreach ($week as $day)
                                <button
                                    type="button"
                                    wire:key="event-day-{{ $day['date'] }}"
                                    wire:click="selectDay('{{ $day['date'] }}')"
                                    @class([
                                        'marketing-agenda__day',
                                        'marketing-agenda__day--outside' => ! $day['isCurrentMonth'],
                                        'marketing-agenda__day--today' => $day['isToday'],
                                        'marketing-agenda__day--selected' => $day['isSelected'],
                                        'marketing-agenda__day--has-events' => $day['count'] > 0,
                                    ])
                                    role="gridcell"
                                    aria-label="{{ $this->formatDayLabel($day['date']) }}{{ $day['count'] > 0 ? ', '.$day['count'].' eventos' : '' }}"
                                    aria-pressed="{{ $day['isSelected'] ? 'true' : 'false' }}"
                                >
                                    <span class="marketing-agenda__day-number">{{ $day['day'] }}</span>

                                    @if ($day['count'] > 0)
                                        <span class="marketing-agenda__day-count" aria-hidden="true">{{ $day['count'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <p class="marketing-agenda__hint">
                    Selecciona un día para ver los eventos corporativos programados.
                </p>
            </section>

            <aside class="marketing-agenda__workspace" aria-label="Eventos del día seleccionado">
                <header class="marketing-agenda__workspace-header">
                    <div class="marketing-agenda__workspace-heading">
                        <p class="marketing-agenda__workspace-kicker">Día seleccionado</p>
                        <h3 class="marketing-agenda__workspace-title">{{ ucfirst($this->formatDayLabel($this->selectedDate)) }}</h3>
                        <p class="marketing-agenda__workspace-subtitle">
                            @if ($this->selectedDayEvents->count() === 0)
                                Sin eventos programados
                            @elseif ($this->selectedDayEvents->count() === 1)
                                1 evento programado
                            @else
                                {{ $this->selectedDayEvents->count() }} eventos programados
                            @endif
                        </p>
                    </div>
                </header>

                <div class="marketing-agenda__workspace-body">
                    @forelse ($this->selectedDayEvents as $event)
                        @php
                            $type = $event->typeEnum();
                            $modality = $event->modalityEnum();
                            $status = $event->statusEnum();
                            $startsAt = $event->starts_at->timezone(config('app.timezone'))->format('H:i');
                        @endphp

                        <article class="marketing-agenda__pub-card marketing-events-agenda__card" wire:key="event-{{ $event->id }}">
                            <div class="marketing-agenda__pub-top">
                                <time class="marketing-agenda__pub-time">{{ $startsAt }}</time>

                                <div class="marketing-agenda__pub-badges">
                                    @if ($type)
                                        <span class="marketing-agenda__pub-badge marketing-agenda__pub-badge--platform">
                                            {{ $type->getLabel() }}
                                        </span>
                                    @endif
                                    <span class="marketing-agenda__pub-badge marketing-agenda__pub-badge--status">
                                        {{ $status->getLabel() }}
                                    </span>
                                </div>
                            </div>

                            <a href="{{ $this->eventViewUrl($event) }}" class="marketing-agenda__pub-main marketing-events-agenda__link">
                                <div class="marketing-agenda__pub-content">
                                    <div class="marketing-agenda__pub-copy">
                                        <h4 class="marketing-agenda__pub-title">{{ $event->title }}</h4>
                                        <p class="marketing-agenda__pub-meta">
                                            {{ $modality?->getLabel() ?? 'Modalidad por confirmar' }}
                                            @if (filled($event->venue_name))
                                                · {{ $event->venue_name }}
                                            @endif
                                        </p>
                                        <p class="marketing-agenda__pub-meta">
                                            @if ($event->hasCapacity())
                                                {{ $event->registrations_count }}/{{ $event->capacity }} inscritos
                                            @else
                                                {{ $event->registrations_count }} inscritos
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @empty
                        <div class="marketing-agenda__empty">
                            <x-filament::icon icon="heroicon-o-calendar-days" class="marketing-agenda__empty-icon" />
                            <p class="marketing-agenda__empty-title">Sin eventos este día</p>
                            <p class="marketing-agenda__empty-copy">Explora otros días del calendario o crea un evento desde el listado.</p>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
