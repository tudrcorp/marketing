<?php

namespace App\Mail;

use App\Filament\Resources\CorporateEvents\Support\CorporateEventPresentation;
use App\Models\CorporateEvent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Storage;

class CorporateEventInvitationEmailAttachments
{
    private const string LOGO_FILENAME = 'logoTDG.png';

    private const string LOGO_PUBLIC_PATH = 'images/logos/'.self::LOGO_FILENAME;

    public static function attach(PendingRequest $request, CorporateEvent $event): PendingRequest
    {
        $request = self::attachLogo($request);

        return self::attachCoverImage($request, $event);
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

    private static function attachCoverImage(
        PendingRequest $request,
        CorporateEvent $event,
    ): PendingRequest {
        if (! CorporateEventPresentation::hasCoverImage($event)) {
            return $request;
        }

        $disk = Storage::disk('public');

        return $request->attach(
            'image',
            $disk->get($event->cover_image),
            basename($event->cover_image),
        );
    }
}
