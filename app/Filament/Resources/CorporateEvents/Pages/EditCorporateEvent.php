<?php

namespace App\Filament\Resources\CorporateEvents\Pages;

use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCorporateEvent extends EditRecord
{
    protected static string $resource = CorporateEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
