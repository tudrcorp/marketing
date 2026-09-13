<?php

namespace App\Filament\Resources\ExternalCompanies\Pages;

use App\Filament\Resources\ExternalCompanies\ExternalCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditExternalCompany extends EditRecord
{
    protected static string $resource = ExternalCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
