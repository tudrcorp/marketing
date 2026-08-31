<x-filament-panels::page class="marketing-agenda">
    <div class="marketing-agenda__shell">
        <aside class="marketing-agenda__filters" aria-label="Filtros del calendario">
            <div class="marketing-agenda__filter">
                <span>Red social</span>
                <div
                    class="marketing-agenda__platform-select"
                    x-data="{
                        open: false,
                        selected: @entangle('platform').live,
                        options: @js($this->platformFilterOptions),
                        get selectedLabel() {
                            if (! this.selected) {
                                return 'Todas las redes';
                            }

                            return this.options.find((option) => option.value === this.selected)?.label ?? 'Todas las redes';
                        },
                        get selectedIcon() {
                            if (! this.selected) {
                                return null;
                            }

                            return this.options.find((option) => option.value === this.selected)?.icon ?? null;
                        },
                        select(value) {
                            this.selected = value;
                            this.open = false;
                        },
                    }"
                    @click.outside="open = false"
                >
                    <button
                        type="button"
                        class="marketing-agenda__platform-trigger"
                        @click="open = ! open"
                        :aria-expanded="open"
                    >
                        <span class="marketing-platform-option">
                            <img
                                x-show="selectedIcon"
                                x-cloak
                                :src="selectedIcon"
                                alt=""
                                class="marketing-platform-option__icon"
                                width="20"
                                height="20"
                            />
                            <span class="marketing-platform-option__label" x-text="selectedLabel"></span>
                        </span>
                        <svg
                            class="marketing-agenda__platform-chevron"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.19l3.71-3.96a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        x-cloak
                        class="marketing-agenda__platform-menu"
                    >
                        <button
                            type="button"
                            class="marketing-agenda__platform-option"
                            :class="{ 'is-selected': ! selected }"
                            @click="select('')"
                        >
                            <span class="marketing-platform-option">
                                <span class="marketing-platform-option__label">Todas las redes</span>
                            </span>
                        </button>

                        <template x-for="option in options" :key="option.value">
                            <button
                                type="button"
                                class="marketing-agenda__platform-option"
                                :class="{ 'is-selected': selected === option.value }"
                                @click="select(option.value)"
                            >
                                <span class="marketing-platform-option">
                                    <img
                                        :src="option.icon"
                                        alt=""
                                        class="marketing-platform-option__icon"
                                        width="20"
                                        height="20"
                                    />
                                    <span class="marketing-platform-option__label" x-text="option.label"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <label class="marketing-agenda__filter">
                <span>Cuenta</span>
                <select wire:model.live="socialAccountId" class="marketing-agenda__select">
                    <option value="">Todas las cuentas</option>
                    @foreach ($this->socialAccountOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </aside>

        <div
            class="marketing-agenda__layout"
            x-data="{
                syncWorkspaceHeight() {
                    const workspace = this.$refs.workspace;
                    const calendar = this.$refs.calendar;

                    if (! workspace || ! calendar) {
                        return;
                    }

                    if (! window.matchMedia('(min-width: 1024px)').matches) {
                        workspace.style.height = '';
                        return;
                    }

                    workspace.style.height = `${calendar.offsetHeight}px`;
                },
            }"
            x-init="
                const sync = () => $data.syncWorkspaceHeight();
                sync();
                $nextTick(sync);
                new ResizeObserver(sync).observe($refs.calendar);
                window.addEventListener('resize', sync);
                Livewire.hook('commit', ({ succeed }) => succeed(() => queueMicrotask(sync)));
            "
        >
            <section
                class="marketing-agenda__calendar"
                x-ref="calendar"
                aria-label="Calendario mensual"
            >
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
                                    wire:key="day-{{ $day['date'] }}"
                                    wire:click="selectDay('{{ $day['date'] }}')"
                                    @class([
                                        'marketing-agenda__day',
                                        'marketing-agenda__day--outside' => ! $day['isCurrentMonth'],
                                        'marketing-agenda__day--today' => $day['isToday'],
                                        'marketing-agenda__day--selected' => $day['isSelected'],
                                        'marketing-agenda__day--has-events' => $day['count'] > 0,
                                    ])
                                    role="gridcell"
                                    aria-label="{{ $this->formatDayLabel($day['date']) }}{{ $day['count'] > 0 ? ', '.$day['count'].' publicaciones'.(filled($day['platforms']) ? ' ('.collect($day['platforms'])->pluck('label')->join(', ').')' : '') : '' }}"
                                    aria-pressed="{{ $day['isSelected'] ? 'true' : 'false' }}"
                                >
                                    <span class="marketing-agenda__day-number">{{ $day['day'] }}</span>

                                    @if (filled($day['platforms']))
                                        <span class="marketing-agenda__day-platforms" aria-hidden="true">
                                            @foreach ($day['platforms'] as $platform)
                                                <img
                                                    src="{{ $platform['icon'] }}"
                                                    alt="{{ $platform['label'] }}"
                                                    title="{{ $platform['label'] }}"
                                                    class="marketing-agenda__day-platform-icon"
                                                    width="14"
                                                    height="14"
                                                />
                                            @endforeach
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <p class="marketing-agenda__hint">
                    @if ($this->canCreatePublications())
                        Selecciona un día actual o futuro para programar publicaciones.
                    @else
                        Selecciona un día para ver las publicaciones programadas.
                    @endif
                </p>
            </section>

            <aside
                class="marketing-agenda__workspace"
                x-ref="workspace"
                aria-label="Publicaciones del día seleccionado"
            >
                <header class="marketing-agenda__workspace-header">
                    <div class="marketing-agenda__workspace-heading">
                        <p class="marketing-agenda__workspace-kicker">Día seleccionado</p>
                        <h3 class="marketing-agenda__workspace-title">{{ ucfirst($this->formatDayLabel($this->selectedDate)) }}</h3>
                        <p class="marketing-agenda__workspace-subtitle">
                            @if ($this->selectedDayPublicationsCount === 0)
                                Sin publicaciones programadas
                            @elseif ($this->selectedDayPublicationsCount === 1)
                                1 publicación programada
                            @else
                                {{ $this->selectedDayPublicationsCount }} publicaciones programadas
                            @endif
                        </p>
                    </div>

                    @if ($this->canCreateOnSelectedDate())
                        <button
                            type="button"
                            wire:click="mountAction('createPublication')"
                            class="marketing-agenda__workspace-cta"
                        >
                            <x-filament::icon icon="heroicon-m-plus" class="size-4" />
                            Nueva
                        </button>
                    @endif
                </header>

                <div class="marketing-agenda__workspace-body">
                    @forelse ($this->selectedDayPublications as $publication)
                        @php
                            $canManage = $this->canManagePublication($publication);
                            $referenceImageUrl = $this->referenceImageUrl($publication->reference_image);
                            $scheduledTime = $publication->scheduled_at->timezone(config('app.timezone'))->format('H:i');
                        @endphp

                        <article
                            class="marketing-agenda__pub-card"
                            wire:key="publication-{{ $publication->id }}"
                        >
                            <div class="marketing-agenda__pub-top">
                                <time class="marketing-agenda__pub-time" datetime="{{ $publication->scheduled_at->toIso8601String() }}">
                                    {{ $scheduledTime }}
                                </time>

                                <div class="marketing-agenda__pub-badges">
                                    <span class="marketing-agenda__pub-badge marketing-agenda__pub-badge--platform">
                                        {{ $publication->socialAccount->platform->getLabel() }}
                                    </span>
                                    <span @class([
                                        'marketing-agenda__pub-badge',
                                        'marketing-agenda__pub-badge--status',
                                        'marketing-agenda__pub-badge--' . $publication->status->value,
                                    ])>
                                        {{ $publication->status->getLabel() }}
                                    </span>
                                </div>
                            </div>

                            <div class="marketing-agenda__pub-row">
                                <button
                                    type="button"
                                    wire:click="mountAction('viewPublication', { publication: {{ $publication->id }} })"
                                    class="marketing-agenda__pub-main"
                                    aria-label="Ver detalle de {{ $publication->title }}"
                                >
                                    <div class="marketing-agenda__pub-content">
                                        @if ($referenceImageUrl)
                                            <img
                                                src="{{ $referenceImageUrl }}"
                                                alt=""
                                                class="marketing-agenda__pub-thumb"
                                                loading="lazy"
                                            />
                                        @else
                                            <div class="marketing-agenda__pub-thumb marketing-agenda__pub-thumb--empty" aria-hidden="true">
                                                <x-filament::icon icon="heroicon-o-photo" class="size-4" />
                                            </div>
                                        @endif

                                        <div class="marketing-agenda__pub-copy">
                                            <h4 class="marketing-agenda__pub-title">{{ $publication->title }}</h4>
                                            <p class="marketing-agenda__pub-preview">{{ Str::limit($publication->body, 80) }}</p>
                                            <p class="marketing-agenda__pub-account">{{ $publication->socialAccount->name }}</p>
                                        </div>
                                    </div>
                                </button>

                                @if ($canManage)
                                    <div class="marketing-agenda__pub-actions">
                                        <button
                                            type="button"
                                            wire:click="mountAction('editPublication', { publication: {{ $publication->id }} })"
                                            class="marketing-agenda__pub-action marketing-agenda__pub-action--edit"
                                            title="Editar publicación"
                                        >
                                            <x-filament::icon icon="heroicon-m-pencil-square" class="size-3.5" />
                                            <span>Editar</span>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="mountAction('deletePublication', { publication: {{ $publication->id }} })"
                                            class="marketing-agenda__pub-action marketing-agenda__pub-action--delete"
                                            title="Eliminar publicación"
                                        >
                                            <x-filament::icon icon="heroicon-m-trash" class="size-3.5" />
                                            <span>Eliminar</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="marketing-agenda__workspace-empty">
                            <div class="marketing-agenda__workspace-empty-icon" aria-hidden="true">
                                <x-filament::icon icon="heroicon-o-calendar-days" class="size-8" />
                            </div>
                            <p class="marketing-agenda__workspace-empty-title">Día libre</p>
                            <p class="marketing-agenda__workspace-empty-copy">
                                @if ($this->canCreateOnSelectedDate())
                                    No hay publicaciones para este día. Puedes programar contenido nuevo cuando lo necesites.
                                @else
                                    Este día ya pasó. Solo puedes consultar publicaciones existentes en fechas anteriores.
                                @endif
                            </p>

                            @if ($this->canCreateOnSelectedDate())
                                <button
                                    type="button"
                                    wire:click="mountAction('createPublication')"
                                    class="marketing-agenda__workspace-empty-cta"
                                >
                                    Programar publicación
                                </button>
                            @endif
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
