<?php

namespace App\Mail;

use App\Models\BirthdayNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class BirthdayNotificationEmailRenderer
{
    public function render(
        BirthdayNotification $notification,
        bool $isTest = false,
        ?string $sentByName = null,
    ): string {
        $useEmbeddedImage = filled($notification->image)
            && Storage::disk('public')->exists($notification->image);

        return View::make('mail.birthday-notification', [
            'notification' => $notification,
            'useEmbeddedImage' => $useEmbeddedImage,
            'isTest' => $isTest,
            'sentByName' => $sentByName,
        ])->render();
    }
}
