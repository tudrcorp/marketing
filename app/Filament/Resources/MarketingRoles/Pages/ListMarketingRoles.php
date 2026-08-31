<?php

namespace App\Filament\Resources\MarketingRoles\Pages;

use App\Filament\Resources\MarketingRoles\MarketingRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketingRoles extends ListRecords
{
    protected static string $resource = MarketingRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
