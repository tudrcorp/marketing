<?php

namespace App\Filament\Resources\EditorialPublications\Pages;

use App\Filament\Resources\EditorialPublications\EditorialPublicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEditorialPublications extends ListRecords
{
    protected static string $resource = EditorialPublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
