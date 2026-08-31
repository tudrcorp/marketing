<?php

namespace App\Filament\Resources\EditorialPublications\Pages;

use App\Filament\Resources\EditorialPublications\EditorialPublicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEditorialPublication extends EditRecord
{
    protected static string $resource = EditorialPublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
