<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CorporateEventStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Promoted = 'promoted';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Promoted => 'Promocionado',
            self::InProgress => 'En curso',
            self::Completed => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'info',
            self::Promoted => 'primary',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::Published => Heroicon::OutlinedMegaphone,
            self::Promoted => Heroicon::OutlinedPaperAirplane,
            self::InProgress => Heroicon::OutlinedPlayCircle,
            self::Completed => Heroicon::OutlinedCheckCircle,
            self::Cancelled => Heroicon::OutlinedXCircle,
        };
    }

    public function isPromotable(): bool
    {
        return in_array($this, [self::Published, self::Promoted], true);
    }

    public function isEditable(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }
}
