<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SocialPlatform: string implements HasColor, HasIcon, HasLabel
{
    case Instagram = 'instagram';
    case YouTube = 'youtube';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case X = 'x';

    /**
     * @return array<int, self>
     */
    public static function orderedCases(): array
    {
        return [
            self::Instagram,
            self::YouTube,
            self::Facebook,
            self::TikTok,
            self::X,
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::YouTube => 'YouTube',
            self::Facebook => 'Facebook',
            self::TikTok => 'TikTok',
            self::X => 'X',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Instagram => 'danger',
            self::YouTube => 'danger',
            self::Facebook => 'info',
            self::TikTok => 'gray',
            self::X => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return Heroicon::OutlinedGlobeAlt;
    }

    public function getImageUrl(): string
    {
        return asset(match ($this) {
            self::Instagram => 'images/icon-red-social/instagram.png',
            self::YouTube => 'images/icon-red-social/youtube.png',
            self::Facebook => 'images/icon-red-social/facebook.png',
            self::TikTok => 'images/icon-red-social/tik-tok.png',
            self::X => 'images/icon-red-social/gorjeo.png',
        });
    }

    public function getSelectOptionHtml(): string
    {
        return sprintf(
            '<span class="marketing-platform-option"><img src="%s" alt="" class="marketing-platform-option__icon" width="20" height="20" /><span class="marketing-platform-option__label">%s</span></span>',
            e($this->getImageUrl()),
            e($this->getLabel()),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        return collect(self::orderedCases())
            ->mapWithKeys(fn (self $platform): array => [
                $platform->value => $platform->getSelectOptionHtml(),
            ])
            ->all();
    }

    public static function selectLabelFor(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $platform = self::tryFrom($value);

        if (! $platform instanceof self) {
            return null;
        }

        return $platform->getSelectOptionHtml();
    }
}
