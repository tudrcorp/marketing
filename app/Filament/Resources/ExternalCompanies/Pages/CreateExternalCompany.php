<?php

namespace App\Filament\Resources\ExternalCompanies\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\ExternalCompanies\ExternalCompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExternalCompany extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = ExternalCompanyResource::class;

    protected function getRedirectUrl(): string
    {
        return ExternalCompanyResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
