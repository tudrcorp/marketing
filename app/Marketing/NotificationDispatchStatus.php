<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum NotificationDispatchStatus: string implements HasColor, HasIcon, HasLabel
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Partial = 'partial';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function getLabel(): string
    {
        return match ($this) {
            self::Queued => 'En cola',
            self::Sent => 'Enviado',
            self::Partial => 'Parcial',
            self::Failed => 'Fallido',
            self::Skipped => 'Omitido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Queued => 'gray',
            self::Sent => 'success',
            self::Partial => 'warning',
            self::Failed => 'danger',
            self::Skipped => 'info',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Queued => Heroicon::OutlinedClock,
            self::Sent => Heroicon::OutlinedCheckCircle,
            self::Partial => Heroicon::OutlinedExclamationTriangle,
            self::Failed => Heroicon::OutlinedXCircle,
            self::Skipped => Heroicon::OutlinedMinusCircle,
        };
    }

    public function isActionable(): bool
    {
        return in_array($this, [self::Failed, self::Partial, self::Skipped], true);
    }
}
