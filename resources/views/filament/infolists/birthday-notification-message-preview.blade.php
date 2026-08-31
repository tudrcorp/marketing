@php
    use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;

    $imageUrl = BirthdayNotificationPresentation::imageUrl($record);
    $channels = BirthdayNotificationPresentation::channelCards($record);
    $primaryChannel = $channels[0]['label'] ?? 'Mensaje';
@endphp

<div class="marketing-birthday-preview">
    <div class="marketing-birthday-preview__layout">
        <div class="marketing-birthday-preview__device" aria-label="Vista previa del mensaje de cumpleaños">
            <header class="marketing-birthday-preview__chat-header">
                <span class="marketing-birthday-preview__avatar">
                    <x-filament::icon icon="heroicon-o-cake" class="size-3.5" />
                </span>
                <div class="marketing-birthday-preview__chat-heading">
                    <p class="marketing-birthday-preview__chat-title">Tu Doctor Group</p>
                    <p class="marketing-birthday-preview__chat-subtitle">Notificación de cumpleaños</p>
                </div>
            </header>

            <div class="marketing-birthday-preview__chat-body">
                <article class="marketing-birthday-preview__message">
                    @if ($imageUrl)
                        <figure class="marketing-birthday-preview__figure">
                            <img
                                src="{{ $imageUrl }}"
                                alt="Imagen de la notificación {{ $record->name }}"
                                class="marketing-birthday-preview__image"
                            />
                        </figure>
                    @endif

                    <div class="marketing-birthday-preview__message-body">
                        @if (filled($record->copy))
                            <p class="marketing-birthday-preview__copy">{{ $record->copy }}</p>
                        @else
                            <p class="marketing-birthday-preview__copy marketing-birthday-preview__copy--empty">
                                Sin texto configurado.
                            </p>
                        @endif
                        <time class="marketing-birthday-preview__time">09:00</time>
                    </div>
                </article>
            </div>
        </div>

        <aside class="marketing-birthday-preview__meta">
            <h3 class="marketing-birthday-preview__meta-title">Resumen</h3>

            <ul class="marketing-birthday-preview__meta-list">
                <li>
                    <span>Canal principal</span>
                    <span>{{ $primaryChannel }}</span>
                </li>
                <li>
                    <span>Caracteres</span>
                    <span>{{ number_format(mb_strlen($record->copy ?? '')) }}</span>
                </li>
                <li>
                    <span>Imagen</span>
                    <span>{{ $imageUrl ? 'Incluida' : 'No incluida' }}</span>
                </li>
                <li>
                    <span>Formato</span>
                    <span>Texto plano</span>
                </li>
            </ul>

            @if ($channels !== [])
                <div class="marketing-birthday-preview__channel-tags">
                    @foreach ($channels as $channel)
                        <span @class([
                            'marketing-birthday-preview__channel-tag',
                            'marketing-birthday-preview__channel-tag--' . $channel['color'],
                        ])>
                            <x-filament::icon :icon="$channel['icon']" class="size-3" />
                            {{ $channel['label'] }}
                        </span>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
</div>
