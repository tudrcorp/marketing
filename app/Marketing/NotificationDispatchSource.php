<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum NotificationDispatchSource: string implements HasColor, HasIcon, HasLabel
{
    case BirthdayTest = 'birthday_test';
    case BirthdayBulk = 'birthday_bulk';
    case MassAudience = 'mass_audience';
    case MassIndividual = 'mass_individual';
    case MassTest = 'mass_test';
    case CorporatePromotion = 'corporate_promotion';
    case CorporateShare = 'corporate_share';

    public function getLabel(): string
    {
        return match ($this) {
            self::BirthdayTest => 'Prueba de cumpleaños',
            self::BirthdayBulk => 'Envío masivo de cumpleaños',
            self::MassAudience => 'Notificación masiva por audiencia',
            self::MassIndividual => 'Notificación masiva individual',
            self::MassTest => 'Prueba de notificación masiva',
            self::CorporatePromotion => 'Promoción de evento corporativo',
            self::CorporateShare => 'Compartir inscripción de evento',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BirthdayTest, self::BirthdayBulk, self::MassTest => 'warning',
            self::MassAudience, self::MassIndividual => 'info',
            self::CorporatePromotion, self::CorporateShare => 'primary',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::BirthdayTest, self::BirthdayBulk => Heroicon::OutlinedCake,
            self::MassAudience, self::MassIndividual => Heroicon::OutlinedBellAlert,
            self::MassTest => Heroicon::OutlinedBeaker,
            self::CorporatePromotion, self::CorporateShare => Heroicon::OutlinedCalendarDays,
        };
    }

    public function isTest(): bool
    {
        return in_array($this, [self::BirthdayTest, self::MassTest], true);
    }
}
