<?php

namespace App\Filament\Resources\IndividualAffiliates\Pages;

use App\Filament\Resources\IndividualAffiliates\IndividualAffiliateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewIndividualAffiliate extends ViewRecord
{
    protected static string $resource = IndividualAffiliateResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        return filled($record->nro_identificacion)
            ? "Identificación {$record->nro_identificacion}"
            : 'Detalle del afiliado individual';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(IndividualAffiliateResource::getUrl('index')),
        ];
    }
}
