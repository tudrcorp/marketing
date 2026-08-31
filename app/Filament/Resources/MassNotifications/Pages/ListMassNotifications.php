<?php

namespace App\Filament\Resources\MassNotifications\Pages;

use App\Filament\Resources\MassNotifications\MassNotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMassNotifications extends ListRecords
{
    protected static string $resource = MassNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
