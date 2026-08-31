<?php

namespace App\Filament\Resources\LegalSuppliers\Pages;

use App\Filament\Resources\LegalSuppliers\LegalSupplierResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageLegalSuppliers extends ManageRecords
{
    protected static string $resource = LegalSupplierResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de proveedores jurídicos y accede al detalle de cada registro.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(fn () => $this->resetTable()),
        ];
    }
}
