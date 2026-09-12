<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ContentPostPriority: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return [self::Urgent, self::High, self::Medium, self::Low];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Low => Heroicon::OutlinedArrowDown,
            self::Medium => Heroicon::OutlinedMinus,
            self::High => Heroicon::OutlinedArrowUp,
            self::Urgent => Heroicon::OutlinedFire,
        };
    }

    public function getHexColor(): string
    {
        return match ($this) {
            self::Low => '#94a3b8',
            self::Medium => '#38bdf8',
            self::High => '#fbbf24',
            self::Urgent => '#f87171',
        };
    }

    /**
     * Peso para ordenar el Modo Máster por urgencia (mayor = más urgente).
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }
}
