<?php

namespace App\Filament\Resources\CorporateAffiliates\Pages;

use App\Filament\Resources\CorporateAffiliates\CorporateAffiliateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageCorporateAffiliates extends ManageRecords
{
    protected static string $resource = CorporateAffiliateResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Consulta el directorio de afiliados corporativos registrados en el API de marketing.';
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
