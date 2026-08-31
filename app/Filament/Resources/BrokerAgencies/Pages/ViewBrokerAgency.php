<?php

namespace App\Filament\Resources\BrokerAgencies\Pages;

use App\Filament\Resources\BrokerAgencies\BrokerAgencyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewBrokerAgency extends ViewRecord
{
    protected static string $resource = BrokerAgencyResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        if (filled($record->code)) {
            return "Código {$record->code}";
        }

        return filled($record->ci_responsable)
            ? "CI {$record->ci_responsable}"
            : 'Detalle de la agencia de corretaje';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(BrokerAgencyResource::getUrl('index')),
        ];
    }
}
