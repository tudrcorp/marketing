@php
    use App\Marketing\BirthdayNotificationChannel;

    $selectedChannels = collect($get('test_channels') ?? [])
        ->map(function (mixed $channel): ?BirthdayNotificationChannel {
            if ($channel instanceof BirthdayNotificationChannel) {
                return $channel;
            }

            return BirthdayNotificationChannel::tryFrom((string) $channel);
        })
        ->filter()
        ->values();

    $needsEmail = $selectedChannels->contains(BirthdayNotificationChannel::Email);
    $needsPhone = $selectedChannels->contains(BirthdayNotificationChannel::WhatsApp)
        || $selectedChannels->contains(BirthdayNotificationChannel::Sms);
@endphp

<div class="marketing-test-send__destinations">
    @if ($selectedChannels->isEmpty())
        <p class="marketing-test-send__destinations-empty">
            Elige al menos un canal para indicar el destinatario.
        </p>
    @else
        <p class="marketing-test-send__destinations-label">Canales seleccionados</p>

        <ul class="marketing-test-send__destinations-list">
            @foreach ($selectedChannels as $channel)
                <li @class([
                    'marketing-test-send__destination-chip',
                    'marketing-test-send__destination-chip--' . $channel->getColor(),
                ])>
                    <x-filament::icon :icon="'heroicon-' . $channel->getIcon()->value" class="size-3.5" />
                    <span>{{ $channel->getLabel() }}</span>
                </li>
            @endforeach
        </ul>

        @if ($needsEmail || $needsPhone)
            <div class="marketing-test-send__destinations-hints">
                @if ($needsEmail)
                    <p>Correo con asunto de prueba.</p>
                @endif

                @if ($needsPhone)
                    <p>Teléfono para WhatsApp y/o SMS.</p>
                @endif
            </div>
        @endif
    @endif
</div>
