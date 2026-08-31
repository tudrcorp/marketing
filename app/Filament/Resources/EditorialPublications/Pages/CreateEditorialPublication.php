<?php

namespace App\Filament\Resources\EditorialPublications\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\EditorialPublications\EditorialPublicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEditorialPublication extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = EditorialPublicationResource::class;
}
