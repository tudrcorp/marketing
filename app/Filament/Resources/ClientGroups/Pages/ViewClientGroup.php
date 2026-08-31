<?php

namespace App\Filament\Resources\ClientGroups\Pages;

use App\Filament\Resources\ClientGroups\ClientGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewClientGroup extends ViewRecord
{
    protected static string $resource = ClientGroupResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Datos del grupo';
    }

    public function getContentTabIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return Heroicon::OutlinedUserGroup;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
