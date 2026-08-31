<?php

namespace App\Filament\Resources\TravelAgencies\Pages;

use App\Filament\Resources\TravelAgencies\TravelAgencyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewTravelAgency extends ViewRecord
{
    protected static string $resource = TravelAgencyResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        return filled($record->numberIdentification)
            ? "ID {$record->numberIdentification}"
            : 'Detalle de la agencia de viajes';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(TravelAgencyResource::getUrl('index')),
        ];
    }
}
