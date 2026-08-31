<?php

namespace App\Filament\Resources\MarketingRoles\Pages;

use App\Filament\Resources\MarketingRoles\MarketingRoleResource;
use App\Marketing\MarketingPermission;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketingRole extends CreateRecord
{
    protected static string $resource = MarketingRoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['permissions'] = MarketingPermission::sanitize($data['permissions'] ?? []);

        return $data;
    }
}
