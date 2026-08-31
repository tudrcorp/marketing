@php
    $isHealthy = $this->health['isHealthy'];
    $statusClass = $isHealthy ? 'marketing-health-glass--healthy' : 'marketing-health-glass--unhealthy';
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => filled($pollingInterval) ? 'refreshHealth' : null,
            ], escape: false)
            ->class(['fi-wi-marketing-health'])
    "
>
    <div @class(['marketing-health-glass', $statusClass])>
        <div class="marketing-health-glass__glow" aria-hidden="true"></div>

        <div class="marketing-health-glass__header">
            <div @class(['marketing-health-glass__icon-wrap', $statusClass])>
                <x-filament::icon
                    :icon="$icon"
                    class="marketing-health-glass__icon"
                />
            </div>

            <div class="marketing-health-glass__meta">
                <p class="marketing-health-glass__title">{{ $title }}</p>
                <p class="marketing-health-glass__description">{{ $description }}</p>
            </div>

            <div @class(['marketing-health-glass__status-pill', $statusClass])>
                <span class="marketing-health-glass__status-dot" aria-hidden="true"></span>
                <span class="marketing-health-glass__status-text">{{ $this->healthStatusText() }}</span>
            </div>
        </div>

        @unless ($isHealthy)
            <div class="marketing-health-glass__body">
                <p class="marketing-health-glass__value">
                    No disponible
                </p>
            </div>
        @endunless
    </div>
</x-filament-widgets::widget>
