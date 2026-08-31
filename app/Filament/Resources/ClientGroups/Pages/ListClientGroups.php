<?php

namespace App\Filament\Resources\ClientGroups\Pages;

use App\Filament\Resources\ClientGroups\ClientGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientGroups extends ListRecords
{
    protected static string $resource = ClientGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo grupo'),
        ];
    }
}
