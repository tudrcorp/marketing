<?php

namespace App\Filament\Resources\BrokerAgents\Pages;

use App\Filament\Resources\BrokerAgents\BrokerAgentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewBrokerAgent extends ViewRecord
{
    protected static string $resource = BrokerAgentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        return filled($record->ci_responsable)
            ? "CI {$record->ci_responsable}"
            : 'Detalle del agente de corretaje';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(BrokerAgentResource::getUrl('index')),
        ];
    }
}
