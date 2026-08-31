<?php

namespace App\Filament\Resources\BrokerAgents\Pages;

use App\Filament\Resources\BrokerAgents\BrokerAgentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageBrokerAgents extends ManageRecords
{
    protected static string $resource = BrokerAgentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de agentes de corretaje y accede al detalle de cada registro.';
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
