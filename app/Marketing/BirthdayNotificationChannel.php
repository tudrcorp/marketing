<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum BirthdayNotificationChannel: string implements HasColor, HasIcon, HasLabel
{
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Sms = 'sms';

    public function getLabel(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Email => 'Correo electrónico',
            self::Sms => 'SMS',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::WhatsApp => 'success',
            self::Email => 'info',
            self::Sms => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::WhatsApp => Heroicon::OutlinedChatBubbleLeftRight,
            self::Email => Heroicon::OutlinedEnvelope,
            self::Sms => Heroicon::OutlinedDevicePhoneMobile,
        };
    }
}
