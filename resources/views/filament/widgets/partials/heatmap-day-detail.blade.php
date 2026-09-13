@php
    /** @var array<string, mixed> $day */
    $events = $this->showsEvents() ? $day['plan']['events'] : [];
    $publications = $this->showsPublications() ? $day['plan']['publications'] : [];
    $total = count($events) + count($publications);

    $summaryParts = [];

    if (count($events) > 0) {
        $summaryParts[] = count($events).' '.(count($events) === 1 ? 'evento' : 'eventos');
    }

    if (count($publications) > 0) {
        $summaryParts[] = count($publications).' '.(count($publications) === 1 ? 'publicación' : 'publicaciones');
    }
@endphp

<div
    data-heatmap-panel="{{ $day['date'] }}"
    class="marketing-heatmap-widget__detail"
    role="region"
    aria-label="Planificación del {{ $this->formatDayHeading($day) }}"
    hidden
>
    <header class="marketing-heatmap-widget__detail-header">
        <div class="marketing-heatmap-widget__detail-heading">
            <span class="marketing-heatmap-widget__detail-daynum">{{ $day['day'] }}</span>

            <div class="min-w-0">
                <p class="marketing-heatmap-widget__detail-date">
                    {{ ucfirst($this->formatDayHeading($day)) }}

                    @if ($day['isToday'])
                        <span class="marketing-heatmap-widget__detail-chip">Hoy</span>
                    @endif

                    <span class="marketing-heatmap-widget__detail-chip marketing-heatmap-widget__detail-chip--muted" data-heatmap-preview-chip hidden>
                        Vista previa
                    </span>
                </p>

                <p class="marketing-heatmap-widget__detail-summary">
                    {{ $total === 0 ? 'Sin actividad programada' : implode(' · ', $summaryParts) }}
                </p>
            </div>
        </div>

        <div class="marketing-heatmap-widget__detail-actions">
            @if ($this->canManageEvents())
                <a href="{{ $this->createCorporateEventUrl() }}" class="marketing-heatmap-widget__detail-action" wire:navigate>
                    <x-filament::icon icon="heroicon-m-plus" class="size-3.5" />
                    Evento
                </a>
            @endif

            @if ($this->canManagePublications())
                <a href="{{ $this->createPublicationUrl() }}" class="marketing-heatmap-widget__detail-action" wire:navigate>
                    <x-filament::icon icon="heroicon-m-plus" class="size-3.5" />
                    Publicación
                </a>
            @endif
        </div>
    </header>

    @if ($total === 0)
        <div class="marketing-heatmap-widget__detail-empty">
            <x-filament::icon icon="heroicon-o-sparkles" class="size-5 shrink-0" />
            <p>Este día está libre. Aprovecha para adelantar la planificación del mes.</p>
        </div>
    @else
        <div class="marketing-heatmap-widget__detail-body">
            @if (count($events) > 0)
                <section class="marketing-heatmap-widget__detail-section">
                    <h5 class="marketing-heatmap-widget__detail-section-title">
                        <span class="marketing-heatmap-widget__stat-dot marketing-heatmap-widget__stat-dot--events" aria-hidden="true"></span>
                        Eventos
                        <span class="marketing-heatmap-widget__detail-section-count">{{ count($events) }}</span>
                    </h5>

                    <ul class="marketing-heatmap-widget__detail-list">
                        @foreach ($events as $event)
                            <li>
                                <a
                                    @class([
                                        'marketing-heatmap-widget__item',
                                        'marketing-heatmap-widget__item--events',
                                        'marketing-heatmap-widget__item--static' => $event['url'] === null,
                                    ])
                                    @if ($event['url'] !== null) href="{{ $event['url'] }}" wire:navigate @endif
                                >
                                    <div class="marketing-heatmap-widget__item-top">
                                        <span class="marketing-heatmap-widget__item-time">{{ $event['time'] }}</span>
                                        <span @class([
                                            'marketing-heatmap-widget__badge',
                                            'marketing-heatmap-widget__badge--'.$event['statusColor'],
                                        ])>{{ $event['status'] }}</span>
                                    </div>

                                    <p class="marketing-heatmap-widget__item-title">{{ $event['title'] }}</p>

                                    @if ($event['type'] || $event['modality'])
                                        <p class="marketing-heatmap-widget__item-meta">
                                            {{ collect([$event['type'], $event['modality']])->filter()->implode(' · ') }}
                                        </p>
                                    @endif

                                    @if ($event['venue'])
                                        <p class="marketing-heatmap-widget__item-meta">
                                            <x-filament::icon icon="heroicon-m-map-pin" class="size-3.5 shrink-0" />
                                            <span class="truncate">{{ $event['venue'] }}</span>
                                        </p>
                                    @endif

                                    @if ($event['url'] !== null)
                                        <span class="marketing-heatmap-widget__item-arrow" aria-hidden="true">
                                            <x-filament::icon icon="heroicon-m-arrow-up-right" class="size-3.5" />
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (count($publications) > 0)
                <section class="marketing-heatmap-widget__detail-section">
                    <h5 class="marketing-heatmap-widget__detail-section-title">
                        <span class="marketing-heatmap-widget__stat-dot marketing-heatmap-widget__stat-dot--publications" aria-hidden="true"></span>
                        Publicaciones
                        <span class="marketing-heatmap-widget__detail-section-count">{{ count($publications) }}</span>
                    </h5>

                    <ul class="marketing-heatmap-widget__detail-list">
                        @foreach ($publications as $publication)
                            <li>
                                <a
                                    @class([
                                        'marketing-heatmap-widget__item',
                                        'marketing-heatmap-widget__item--publications',
                                        'marketing-heatmap-widget__item--static' => $publication['url'] === null,
                                    ])
                                    @if ($publication['url'] !== null) href="{{ $publication['url'] }}" wire:navigate @endif
                                >
                                    <div class="marketing-heatmap-widget__item-top">
                                        <span class="marketing-heatmap-widget__item-time">{{ $publication['time'] }}</span>
                                        <span @class([
                                            'marketing-heatmap-widget__badge',
                                            'marketing-heatmap-widget__badge--'.$publication['statusColor'],
                                        ])>{{ $publication['status'] }}</span>
                                    </div>

                                    <p class="marketing-heatmap-widget__item-title">{{ $publication['title'] }}</p>

                                    @if ($publication['platform'] || $publication['account'])
                                        <p class="marketing-heatmap-widget__item-meta">
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
                                            <span class="truncate">{{ collect([$publication['platform'], $publication['account']])->filter()->implode(' · ') }}</span>
                                        </p>
                                    @endif

                                    @if ($publication['url'] !== null)
                                        <span class="marketing-heatmap-widget__item-arrow" aria-hidden="true">
                                            <x-filament::icon icon="heroicon-m-arrow-up-right" class="size-3.5" />
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif
</div>
