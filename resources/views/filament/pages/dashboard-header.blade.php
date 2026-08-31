<header class="fi-header marketing-dashboard-header">
    <div>
        <h1 class="fi-header-heading">
            {{ $heading }}
        </h1>

        @if (filled($subheading))
            <p class="fi-header-subheading">
                {{ $subheading }}
            </p>
        @endif
    </div>

    @livewire(\App\Filament\Widgets\MarketingDashboardHealthIndicatorsWidget::class)
</header>
