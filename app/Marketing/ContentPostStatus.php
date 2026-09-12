<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ContentPostStatus: string implements HasColor, HasIcon, HasLabel
{
    case Idea = 'idea';
    case Writing = 'writing';
    case Design = 'design';
    case Approval = 'approval';
    case Scheduled = 'scheduled';
    case Published = 'published';

    /**
     * Orden de las columnas del tablero Kanban.
     *
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return [
            self::Idea,
            self::Writing,
            self::Design,
            self::Approval,
            self::Scheduled,
            self::Published,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Idea => 'Idea',
            self::Writing => 'En redacción',
            self::Design => 'En diseño',
            self::Approval => 'Aprobación',
            self::Scheduled => 'Programado',
            self::Published => 'Publicado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Idea => 'gray',
            self::Writing => 'info',
            self::Design => 'secondary',
            self::Approval => 'warning',
            self::Scheduled => 'primary',
            self::Published => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Idea => Heroicon::OutlinedLightBulb,
            self::Writing => Heroicon::OutlinedPencilSquare,
            self::Design => Heroicon::OutlinedPaintBrush,
            self::Approval => Heroicon::OutlinedEye,
            self::Scheduled => Heroicon::OutlinedCalendarDays,
            self::Published => Heroicon::OutlinedCheckCircle,
        };
    }

    /**
     * Color HEX usado por el tablero y el calendario (fuera de la paleta de Filament).
     */
    public function getHexColor(): string
    {
        return match ($this) {
            self::Idea => '#94a3b8',
            self::Writing => '#38bdf8',
            self::Design => '#a78bfa',
            self::Approval => '#fbbf24',
            self::Scheduled => '#f9b17a',
            self::Published => '#34d399',
        };
    }

    /**
     * Tipo de trabajo asociado, usado por el filtro de batching (trabajo por lotes).
     */
    public function batchingLabel(): string
    {
        return match ($this) {
            self::Idea => 'Conceptualización',
            self::Writing => 'Copywriting',
            self::Design => 'Diseño',
            self::Approval => 'Revisión y aprobación',
            self::Scheduled => 'Programación',
            self::Published => 'Publicado',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Published;
    }
}
