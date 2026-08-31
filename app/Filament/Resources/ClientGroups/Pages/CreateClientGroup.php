<?php

namespace App\Filament\Resources\ClientGroups\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\ClientGroups\ClientGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClientGroup extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = ClientGroupResource::class;

    protected function getRedirectUrl(): string
    {
        return ClientGroupResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
