<?php

namespace App\Filament\Resources\LegalSuppliers\Pages;

use App\Filament\Resources\LegalSuppliers\LegalSupplierResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewLegalSupplier extends ViewRecord
{
    protected static string $resource = LegalSupplierResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        return filled($record->rif)
            ? "RIF {$record->rif}"
            : 'Detalle del proveedor jurídico';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(LegalSupplierResource::getUrl('index')),
        ];
    }
}
