<?php

namespace App\Filament\Resources\TravelAgents\Pages;

use App\Filament\Resources\TravelAgents\TravelAgentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewTravelAgent extends ViewRecord
{
    protected static string $resource = TravelAgentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Detalle del agente de viajes';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(TravelAgentResource::getUrl('index')),
        ];
    }
}
