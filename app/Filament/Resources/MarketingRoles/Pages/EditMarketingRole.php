<?php

namespace App\Filament\Resources\MarketingRoles\Pages;

use App\Filament\Resources\MarketingRoles\MarketingRoleResource;
use App\Marketing\MarketingPermission;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketingRole extends EditRecord
{
    protected static string $resource = MarketingRoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = MarketingPermission::sanitize($data['permissions'] ?? []);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['permissions'] = MarketingPermission::sanitize($data['permissions'] ?? []);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
