<?php

namespace App\Filament\Resources\NaturalSuppliers\Pages;

use App\Filament\Resources\NaturalSuppliers\NaturalSupplierResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageNaturalSuppliers extends ManageRecords
{
    protected static string $resource = NaturalSupplierResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de proveedores naturales y accede al detalle de cada registro.';
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
