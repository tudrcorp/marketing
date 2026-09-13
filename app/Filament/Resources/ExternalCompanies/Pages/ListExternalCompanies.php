<?php

namespace App\Filament\Resources\ExternalCompanies\Pages;

use App\Filament\Resources\ExternalCompanies\Actions\ImportExternalCompaniesAction;
use App\Filament\Resources\ExternalCompanies\ExternalCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExternalCompanies extends ListRecords
{
    protected static string $resource = ExternalCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportExternalCompaniesAction::make(),
            CreateAction::make()
                ->label('Nuevo externo'),
        ];
    }
}
