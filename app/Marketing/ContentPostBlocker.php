<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ContentPostBlocker: string implements HasColor, HasIcon, HasLabel
{
    case None = 'none';
    case MissingClientVideo = 'missing_client_video';
    case InReview = 'in_review';
    case MissingArt = 'missing_art';

    /**
     * @return list<self>
     */
    public static function blockingCases(): array
    {
        return [self::MissingClientVideo, self::InReview, self::MissingArt];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'Ninguno',
            self::MissingClientVideo => 'Falta video del cliente',
            self::InReview => 'En revisión',
            self::MissingArt => 'Falta arte',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::MissingClientVideo => 'danger',
            self::InReview => 'warning',
            self::MissingArt => 'warning',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::None => Heroicon::OutlinedCheck,
            self::MissingClientVideo => Heroicon::OutlinedVideoCameraSlash,
            self::InReview => Heroicon::OutlinedClock,
            self::MissingArt => Heroicon::OutlinedPhoto,
        };
    }

    public function getHexColor(): string
    {
        return match ($this) {
            self::None => '#94a3b8',
            self::MissingClientVideo => '#f87171',
            self::InReview => '#fbbf24',
            self::MissingArt => '#fb923c',
        };
    }

    public function isBlocking(): bool
    {
        return $this !== self::None;
    }
}
