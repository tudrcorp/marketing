<?php

namespace App\Filament\Resources\MassNotifications\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\MassNotifications\MassNotificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMassNotification extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = MassNotificationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
