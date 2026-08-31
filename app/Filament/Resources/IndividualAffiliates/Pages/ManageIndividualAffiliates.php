<?php

namespace App\Filament\Resources\IndividualAffiliates\Pages;

use App\Filament\Resources\IndividualAffiliates\IndividualAffiliateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageIndividualAffiliates extends ManageRecords
{
    protected static string $resource = IndividualAffiliateResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Consulta el directorio de afiliados individuales registrados en el API de marketing.';
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
