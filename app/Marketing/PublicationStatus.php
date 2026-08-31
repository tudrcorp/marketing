<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum PublicationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingApproval => 'Pendiente de aprobación',
            self::Scheduled => 'Programada',
            self::Published => 'Publicada',
            self::Failed => 'Fallida',
            self::Cancelled => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Scheduled => 'info',
            self::Published => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::OutlinedPencilSquare,
            self::PendingApproval => Heroicon::OutlinedClock,
            self::Scheduled => Heroicon::OutlinedCalendarDays,
            self::Published => Heroicon::OutlinedCheckCircle,
            self::Failed => Heroicon::OutlinedExclamationTriangle,
            self::Cancelled => Heroicon::OutlinedXCircle,
        };
    }
}
