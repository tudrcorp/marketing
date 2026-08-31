<?php

namespace App\Filament\Resources\TravelAgents\Pages;

use App\Filament\Resources\TravelAgents\TravelAgentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageTravelAgents extends ManageRecords
{
    protected static string $resource = TravelAgentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de agentes de viajes y accede al detalle de cada registro.';
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
