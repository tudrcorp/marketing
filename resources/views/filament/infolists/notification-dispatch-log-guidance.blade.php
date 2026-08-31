@php
    use App\Filament\Resources\NotificationDispatchLogs\Support\NotificationDispatchLogPresentation;

    /** @var \App\Models\NotificationDispatchLog $record */
    $glassClass = NotificationDispatchLogPresentation::statusGlassClass($record);
@endphp

<section @class(['marketing-dispatch-log-glass', $glassClass])>
    <div class="marketing-dispatch-log-glass__glow" aria-hidden="true"></div>

    <header class="marketing-dispatch-log-glass__header">
        <div @class([
            'marketing-dispatch-log-glass__icon-wrap',
            $glassClass,
        ])>
            <x-filament::icon
                :icon="$record->statusEnum()->getIcon()"
                class="marketing-dispatch-log-glass__icon"
            />
        </div>

        <div class="marketing-dispatch-log-glass__meta">
            <p class="marketing-dispatch-log-glass__eyebrow">Te explicamos qué pasó</p>
            <h2 class="marketing-dispatch-log-glass__title">{{ $record->analyst_message }}</h2>
            <p class="marketing-dispatch-log-glass__summary">{{ $record->summary }}</p>
        </div>

        <span @class([
            'marketing-dispatch-log-glass__status-pill',
            $glassClass,
        ])>
            <span class="marketing-dispatch-log-glass__status-dot" aria-hidden="true"></span>
            {{ $record->statusEnum()->getLabel() }}
        </span>
    </header>

    <div class="marketing-dispatch-log-glass__body">
        <h3 class="marketing-dispatch-log-glass__section-title">
            <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="size-4" />
            Cómo resolverlo
        </h3>
        <div class="marketing-dispatch-log-glass__steps">
            @foreach (preg_split('/\r\n|\r|\n/', (string) $record->resolution_steps) ?: [] as $step)
                @if (filled(trim($step)))
                    <p class="marketing-dispatch-log-glass__step">{{ $step }}</p>
                @endif
            @endforeach
        </div>
    </div>

    <footer class="marketing-dispatch-log-glass__footer">
        @if (filled($record->failure_code))
            <span class="marketing-dispatch-log-glass__chip">{{ $record->failure_code }}</span>
        @endif
        <span class="marketing-dispatch-log-glass__chip">ID {{ $record->getKey() }}</span>
        @if ($record->logged_at)
            <span class="marketing-dispatch-log-glass__chip">{{ $record->logged_at->format('d/m/Y H:i') }}</span>
        @endif
    </footer>
</section>
