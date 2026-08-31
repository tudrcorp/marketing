<?php

namespace App\Mail;

use App\Models\MassNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class MassNotificationEmailRenderer
{
    public function render(
        MassNotification $notification,
        ?string $sentByName = null,
    ): string {
        $useEmbeddedImage = $notification->contentTypeEnum()?->value === 'image'
            && filled($notification->attachment)
            && Storage::disk('public')->exists($notification->attachment);

        return View::make('mail.mass-notification', [
            'notification' => $notification,
            'useEmbeddedImage' => $useEmbeddedImage,
            'sentByName' => $sentByName,
        ])->render();
    }
}
