<?php

namespace App\Filament\Resources\BirthdayNotifications\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\BirthdayNotifications\BirthdayNotificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBirthdayNotification extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = BirthdayNotificationResource::class;
}
