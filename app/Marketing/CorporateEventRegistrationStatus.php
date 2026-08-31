<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CorporateEventRegistrationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Registered = 'registered';
    case Attended = 'attended';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::Registered => 'Inscrito',
            self::Attended => 'Asistió',
            self::Cancelled => 'Cancelado',
            self::NoShow => 'No asistió',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Registered => 'info',
            self::Attended => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Registered => Heroicon::OutlinedTicket,
            self::Attended => Heroicon::OutlinedCheckBadge,
            self::Cancelled => Heroicon::OutlinedXMark,
            self::NoShow => Heroicon::OutlinedMinusCircle,
        };
    }
}
