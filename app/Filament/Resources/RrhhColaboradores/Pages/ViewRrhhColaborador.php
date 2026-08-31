<?php

namespace App\Filament\Resources\RrhhColaboradores\Pages;

use App\Filament\Resources\RrhhColaboradores\RrhhColaboradorResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewRrhhColaborador extends ViewRecord
{
    protected static string $resource = RrhhColaboradorResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Detalle del colaborador';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(RrhhColaboradorResource::getUrl('index')),
        ];
    }
}
