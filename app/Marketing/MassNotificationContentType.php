<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum MassNotificationContentType: string implements HasColor, HasIcon, HasLabel
{
    case Image = 'image';
    case Video = 'video';
    case File = 'file';

    public function getLabel(): string
    {
        return match ($this) {
            self::Image => 'Imagen',
            self::Video => 'Video',
            self::File => 'Archivo',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Image => 'info',
            self::Video => 'warning',
            self::File => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Image => Heroicon::OutlinedPhoto,
            self::Video => Heroicon::OutlinedFilm,
            self::File => Heroicon::OutlinedDocument,
        };
    }

    /**
     * @return list<string>
     */
    public function acceptedMimeTypes(): array
    {
        return match ($this) {
            self::Image => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            self::Video => ['video/mp4', 'video/webm', 'video/quicktime'],
            self::File => [],
        };
    }

    public static function tryFromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (blank($value)) {
            return null;
        }

        return self::tryFrom((string) $value);
    }
}
