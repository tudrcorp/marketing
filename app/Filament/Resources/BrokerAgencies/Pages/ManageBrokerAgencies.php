<?php

namespace App\Filament\Resources\BrokerAgencies\Pages;

use App\Filament\Resources\BrokerAgencies\BrokerAgencyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageBrokerAgencies extends ManageRecords
{
    protected static string $resource = BrokerAgencyResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de agencias de corretaje y accede al detalle de cada registro.';
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
