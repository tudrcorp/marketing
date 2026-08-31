<?php

namespace App\Filament\Resources\BirthdayNotifications\Pages;

use App\Filament\Resources\BirthdayNotifications\BirthdayNotificationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBirthdayNotification extends EditRecord
{
    protected static string $resource = BirthdayNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
