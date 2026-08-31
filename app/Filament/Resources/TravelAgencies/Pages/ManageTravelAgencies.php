<?php

namespace App\Filament\Resources\TravelAgencies\Pages;

use App\Filament\Resources\TravelAgencies\TravelAgencyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageTravelAgencies extends ManageRecords
{
    protected static string $resource = TravelAgencyResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de agencias de viajes y accede al detalle de cada registro.';
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
