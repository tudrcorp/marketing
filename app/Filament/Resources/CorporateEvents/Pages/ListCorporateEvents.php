<?php

namespace App\Filament\Resources\CorporateEvents\Pages;

use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCorporateEvents extends ListRecords
{
    protected static string $resource = CorporateEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
