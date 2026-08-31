@php
    use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;

    $channels = BirthdayNotificationPresentation::channelCards($record);
    $audienceGroups = BirthdayNotificationPresentation::audienceGroups($record);
    $stats = BirthdayNotificationPresentation::stats($record);
@endphp

<div class="marketing-birthday-distribution">
    <section class="marketing-birthday-distribution__section">
        <header class="marketing-birthday-distribution__section-header">
            <x-filament::icon icon="heroicon-o-paper-airplane" class="size-5" />
            <div>
                <h3 class="marketing-birthday-distribution__section-title">Canales de envío</h3>
                <p class="marketing-birthday-distribution__section-description">
                    Medios por los que se entregará el mensaje de cumpleaños.
                </p>
            </div>
            <span class="marketing-birthday-distribution__count">{{ $stats['channels'] }}</span>
        </header>

        @if ($channels === [])
            <p class="marketing-birthday-distribution__empty">No hay canales seleccionados.</p>
        @else
            <div class="marketing-birthday-distribution__channels">
                @foreach ($channels as $channel)
                    <article @class([
                        'marketing-birthday-channel-card',
                        'marketing-birthday-channel-card--' . $channel['color'],
                    ])>
                        <span class="marketing-birthday-channel-card__icon">
                            <x-filament::icon :icon="$channel['icon']" class="size-6" />
                        </span>
                        <div>
                            <p class="marketing-birthday-channel-card__label">{{ $channel['label'] }}</p>
                            <p class="marketing-birthday-channel-card__hint">Canal activo</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="marketing-birthday-distribution__section">
        <header class="marketing-birthday-distribution__section-header">
            <x-filament::icon icon="heroicon-o-users" class="size-5" />
            <div>
                <h3 class="marketing-birthday-distribution__section-title">Grupos destinatarios</h3>
                <p class="marketing-birthday-distribution__section-description">
                    Audiencias que recibirán esta felicitación automática.
                </p>
            </div>
            <span class="marketing-birthday-distribution__count">{{ $stats['audiences'] }}</span>
        </header>

        @if ($audienceGroups === [])
            <p class="marketing-birthday-distribution__empty">No hay grupos destinatarios seleccionados.</p>
        @else
            <div class="marketing-birthday-distribution__groups">
                @foreach ($audienceGroups as $group)
                    <article class="marketing-birthday-audience-group">
                        <header class="marketing-birthday-audience-group__header">
                            <x-filament::icon :icon="$group['icon']" class="size-4" />
                            <h4>{{ $group['label'] }}</h4>
                            <span>{{ count($group['items']) }}</span>
                        </header>
                        <ul class="marketing-birthday-audience-group__list">
                            @foreach ($group['items'] as $item)
                                <li class="marketing-birthday-audience-group__item">
                                    <x-filament::icon :icon="$item['icon']" class="size-4" />
                                    <span>{{ $item['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
