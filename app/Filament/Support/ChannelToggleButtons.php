<?php

namespace App\Filament\Support;

use App\Marketing\BirthdayNotificationChannel;
use Filament\Forms\Components\ToggleButtons;

class ChannelToggleButtons
{
    public static function make(
        string $name,
        ?string $label = 'Canales',
        bool $hiddenLabel = false,
        bool $inline = false,
        bool $multiple = true,
    ): ToggleButtons {
        $field = ToggleButtons::make($name)
            ->options(BirthdayNotificationChannel::class)
            ->live()
            ->required()
            ->tooltips([
                BirthdayNotificationChannel::WhatsApp->value => 'Mensaje directo al teléfono del destinatario vía WhatsApp.',
                BirthdayNotificationChannel::Email->value => 'Correo electrónico con el copy y adjuntos de la campaña.',
                BirthdayNotificationChannel::Sms->value => 'Texto corto al número móvil del destinatario.',
            ])
            ->extraAttributes([
                'class' => $inline
                    ? 'marketing-channel-toggle marketing-channel-toggle--inline'
                    : 'marketing-channel-toggle marketing-channel-toggle--cards',
            ])
            ->columnSpanFull();

        if ($multiple) {
            $field->multiple();
        }

        if ($hiddenLabel) {
            $field->hiddenLabel();
        } elseif ($label !== null) {
            $field->label($label);
        }

        if ($inline) {
            $field->inline();
        } else {
            $field->columns(3);
        }

        return $field;
    }
}
