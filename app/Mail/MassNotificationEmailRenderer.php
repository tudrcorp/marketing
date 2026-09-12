<?php

namespace App\Mail;

use App\Models\MassNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class MassNotificationEmailRenderer
{
    private const string LOGO_PUBLIC_PATH = 'images/logos/logoTDG.png';

    public function render(
        MassNotification $notification,
        ?string $sentByName = null,
        bool $forTransactional = false,
    ): string {
        $useHostedImage = $notification->contentTypeEnum()?->value === 'image'
            && filled($notification->attachment)
            && Storage::disk('public')->exists($notification->attachment);

        return View::make('mail.mass-notification', [
            'notification' => $notification,
            'useHostedImage' => $useHostedImage,
            'forTransactional' => $forTransactional,
            'logoUrl' => $forTransactional ? 'cid:company-logo' : $this->logoUrl(),
            'imageUrl' => $useHostedImage
                ? ($forTransactional ? 'cid:campaign-image' : $this->publicUrl($notification->attachment))
                : null,
            'attachmentUrl' => $forTransactional ? null : $this->attachmentUrl($notification, $useHostedImage),
            'sentByName' => $sentByName,
        ])->render();
    }

    private function logoUrl(): string
    {
        return asset(self::LOGO_PUBLIC_PATH);
    }

    private function attachmentUrl(MassNotification $notification, bool $useHostedImage): ?string
    {
        if ($useHostedImage || ! filled($notification->attachment)) {
            return null;
        }

        if (! Storage::disk('public')->exists($notification->attachment)) {
            return null;
        }

        return $this->publicUrl($notification->attachment);
    }

    private function publicUrl(string $path): string
    {
        $url = Storage::disk('public')->url($path);

        if (app()->environment('local') && str_contains($url, '.test')) {
            return str_replace('https://', 'http://', $url);
        }

        return $url;
    }
}
