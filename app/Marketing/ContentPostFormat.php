<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ContentPostFormat: string implements HasIcon, HasLabel
{
    case Reel = 'reel';
    case Carousel = 'carousel';
    case Static = 'static';
    case Stories = 'stories';

    /**
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return [self::Reel, self::Carousel, self::Static, self::Stories];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Reel => 'Reel',
            self::Carousel => 'Carrusel',
            self::Static => 'Estático',
            self::Stories => 'Historias',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Reel => Heroicon::OutlinedFilm,
            self::Carousel => Heroicon::OutlinedRectangleStack,
            self::Static => Heroicon::OutlinedPhoto,
            self::Stories => Heroicon::OutlinedSparkles,
        };
    }

    /**
     * Los formatos efímeros no ocupan lugar en la cuadrícula del perfil.
     */
    public function appearsInFeed(): bool
    {
        return $this !== self::Stories;
    }
}
