<div
    class="marketing-dashboard-health-indicators"
    @if ($this->getPollingInterval())
        wire:poll.{{ $this->getPollingInterval() }}="refreshHealth"
    @endif
    role="status"
    aria-label="Estado de servicios"
>
    @foreach ([
        ['health' => $apiHealth, 'icon' => 'heroicon-o-server-stack'],
        ['health' => $databaseHealth, 'icon' => 'heroicon-o-circle-stack'],
    ] as $indicator)
        @php
            $isHealthy = $indicator['health']['isHealthy'];
            $statusClass = $isHealthy ? 'marketing-dashboard-health-indicator--healthy' : 'marketing-dashboard-health-indicator--unhealthy';
        @endphp

        <div @class(['marketing-dashboard-health-indicator', $statusClass])>
            <span @class(['marketing-dashboard-health-indicator__icon-wrap', $statusClass])>
                <x-filament::icon :icon="$indicator['icon']" class="marketing-dashboard-health-indicator__icon" />
            </span>

            <span class="marketing-dashboard-health-indicator__label">
                {{ $indicator['health']['label'] }}
            </span>

            <span @class(['marketing-dashboard-health-indicator__status', $statusClass])>
                <span class="marketing-dashboard-health-indicator__dot" aria-hidden="true"></span>
                {{ $indicator['health']['statusText'] }}
            </span>
        </div>
    @endforeach
</div>
