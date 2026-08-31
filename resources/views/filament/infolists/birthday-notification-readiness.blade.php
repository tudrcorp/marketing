@php
    use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;

    $checks = BirthdayNotificationPresentation::readinessChecks($record);
    $isReady = BirthdayNotificationPresentation::stats($record)['is_ready'];
@endphp

<div @class([
    'marketing-birthday-readiness',
    'marketing-birthday-readiness--ready' => $isReady,
    'marketing-birthday-readiness--pending' => ! $isReady,
])>
    <header class="marketing-birthday-readiness__header">
        <x-filament::icon
            :icon="$isReady ? 'heroicon-o-check-badge' : 'heroicon-o-exclamation-triangle'"
            class="size-5"
        />
        <div>
            <h3 class="marketing-birthday-readiness__title">
                {{ $isReady ? 'Plantilla lista para programar envíos' : 'Completa la configuración' }}
            </h3>
            <p class="marketing-birthday-readiness__subtitle">
                {{ $isReady
                    ? 'El mensaje, los canales y las audiencias están definidos.'
                    : 'Revisa los puntos pendientes antes de usar esta plantilla.' }}
            </p>
        </div>
    </header>

    <ul class="marketing-birthday-readiness__list">
        @foreach ($checks as $check)
            <li @class([
                'marketing-birthday-readiness__item',
                'marketing-birthday-readiness__item--met' => $check['met'],
                'marketing-birthday-readiness__item--pending' => ! $check['met'],
            ])>
                <span class="marketing-birthday-readiness__indicator">
                    <x-filament::icon
                        :icon="$check['met'] ? 'heroicon-m-check' : 'heroicon-m-minus'"
                        class="size-3.5"
                    />
                </span>
                <div>
                    <p class="marketing-birthday-readiness__item-label">{{ $check['label'] }}</p>
                    <p class="marketing-birthday-readiness__item-hint">{{ $check['hint'] }}</p>
                </div>
            </li>
        @endforeach
    </ul>
</div>
