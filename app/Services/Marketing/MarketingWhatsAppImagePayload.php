<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketingWhatsAppImagePayload
{
    /**
     * @return array{image_url?: string, image_base64?: string}|null
     */
    public static function fromStoragePath(?string $storagePath, string $disk = 'public'): ?array
    {
        if (! filled($storagePath)) {
            return null;
        }

        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($storagePath)) {
            return null;
        }

        $publicUrl = $filesystem->url($storagePath);

        if (self::isPubliclyAccessibleUrl($publicUrl)) {
            return ['image_url' => $publicUrl];
        }

        $mimeType = $filesystem->mimeType($storagePath) ?: 'image/jpeg';
        $contents = $filesystem->get($storagePath);

        if ($contents === null || $contents === '') {
            return null;
        }

        return [
            'image_base64' => 'data:'.$mimeType.';base64,'.base64_encode($contents),
        ];
    }

    public static function isPubliclyAccessibleUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = Str::lower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'], true)) {
            return false;
        }

        if (str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        return true;
    }
}
