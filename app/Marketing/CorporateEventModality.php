<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CorporateEventModality: string implements HasColor, HasIcon, HasLabel
{
    case InPerson = 'in_person';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::InPerson => 'Presencial',
            self::Virtual => 'Virtual',
            self::Hybrid => 'Híbrido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InPerson => 'success',
            self::Virtual => 'info',
            self::Hybrid => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::InPerson => Heroicon::OutlinedMapPin,
            self::Virtual => Heroicon::OutlinedVideoCamera,
            self::Hybrid => Heroicon::OutlinedArrowsRightLeft,
        };
    }
}
