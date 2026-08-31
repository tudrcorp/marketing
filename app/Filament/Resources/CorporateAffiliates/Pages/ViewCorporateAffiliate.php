<?php

namespace App\Filament\Resources\CorporateAffiliates\Pages;

use App\Filament\Resources\CorporateAffiliates\CorporateAffiliateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewCorporateAffiliate extends ViewRecord
{
    protected static string $resource = CorporateAffiliateResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();

        return filled($record->nro_identificacion)
            ? "Identificación {$record->nro_identificacion}"
            : 'Detalle del afiliado corporativo';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(CorporateAffiliateResource::getUrl('index')),
        ];
    }
}
