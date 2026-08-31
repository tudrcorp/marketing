<?php

namespace App\Mail;

use App\Models\MassNotification;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Storage;

class MassNotificationEmailAttachments
{
    private const string LOGO_FILENAME = 'logoTDG.png';

    private const string LOGO_PUBLIC_PATH = 'images/logos/'.self::LOGO_FILENAME;

    public static function attach(PendingRequest $request, MassNotification $notification): PendingRequest
    {
        $request = self::attachLogo($request);

        if (! filled($notification->attachment)) {
            return $request;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($notification->attachment)) {
            return $request;
        }

        $fieldName = $notification->contentTypeEnum()?->value === 'image' ? 'image' : 'attachment';

        return $request->attach(
            $fieldName,
            $disk->get($notification->attachment),
            basename($notification->attachment),
        );
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
}
