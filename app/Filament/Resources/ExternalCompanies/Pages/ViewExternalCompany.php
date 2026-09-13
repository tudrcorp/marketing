<?php

namespace App\Filament\Resources\ExternalCompanies\Pages;

use App\Filament\Resources\ExternalCompanies\ExternalCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewExternalCompany extends ViewRecord
{
    protected static string $resource = ExternalCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
