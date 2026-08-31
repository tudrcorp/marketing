<?php

namespace App\Filament\Resources\NaturalSuppliers\Pages;

use App\Filament\Resources\NaturalSuppliers\NaturalSupplierResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewNaturalSupplier extends ViewRecord
{
    protected static string $resource = NaturalSupplierResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Detalle del proveedor natural';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(NaturalSupplierResource::getUrl('index')),
        ];
    }
}
