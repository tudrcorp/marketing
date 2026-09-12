<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasLabel;

enum ContentPillar: string implements HasLabel
{
    case Educational = 'educational';
    case Inspirational = 'inspirational';
    case Promotional = 'promotional';
    case Entertainment = 'entertainment';
    case Testimonial = 'testimonial';
    case BehindTheScenes = 'behind_the_scenes';

    /**
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return self::cases();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Educational => 'Educativo',
            self::Inspirational => 'Inspiracional',
            self::Promotional => 'Promocional',
            self::Entertainment => 'Entretenimiento',
            self::Testimonial => 'Testimonial',
            self::BehindTheScenes => 'Detrás de cámaras',
        };
    }

    public function getHexColor(): string
    {
        return match ($this) {
            self::Educational => '#38bdf8',
            self::Inspirational => '#a78bfa',
            self::Promotional => '#f9b17a',
            self::Entertainment => '#f472b6',
            self::Testimonial => '#34d399',
            self::BehindTheScenes => '#94a3b8',
        };
    }
}
