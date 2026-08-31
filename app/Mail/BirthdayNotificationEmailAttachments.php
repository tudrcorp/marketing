<?php

namespace App\Mail;

use App\Models\BirthdayNotification;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Storage;

class BirthdayNotificationEmailAttachments
{
    private const string LOGO_FILENAME = 'logoTDG.png';

    private const string LOGO_PUBLIC_PATH = 'images/logos/'.self::LOGO_FILENAME;

    public static function attach(PendingRequest $request, BirthdayNotification $notification): PendingRequest
    {
        $request = self::attachLogo($request);

        return self::attachCampaignImage($request, $notification);
    }

    private static function attachLogo(PendingRequest $request): PendingRequest
    {
        $path = public_path(self::LOGO_PUBLIC_PATH);

        if (! is_readable($path)) {
            return $request;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return $request;
        }

        return $request->attach('logo', $contents, self::LOGO_FILENAME);
    }

    private static function attachCampaignImage(
        PendingRequest $request,
        BirthdayNotification $notification,
    ): PendingRequest {
        if (! filled($notification->image)) {
            return $request;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($notification->image)) {
            return $request;
        }

        return $request->attach(
            'image',
            $disk->get($notification->image),
            basename($notification->image),
        );
    }
}
