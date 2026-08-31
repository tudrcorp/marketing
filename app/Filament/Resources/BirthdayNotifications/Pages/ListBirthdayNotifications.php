<?php

namespace App\Filament\Resources\BirthdayNotifications\Pages;

use App\Filament\Resources\BirthdayNotifications\BirthdayNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBirthdayNotifications extends ListRecords
{
    protected static string $resource = BirthdayNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
