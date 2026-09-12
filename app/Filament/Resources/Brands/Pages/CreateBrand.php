<?php

namespace App\Filament\Resources\Brands\Pages;

use App\Filament\Concerns\SetsMarketingAuthor;
use App\Filament\Resources\Brands\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    use SetsMarketingAuthor;

    protected static string $resource = BrandResource::class;

    protected function getRedirectUrl(): string
    {
        return BrandResource::getUrl('index');
    }
}
