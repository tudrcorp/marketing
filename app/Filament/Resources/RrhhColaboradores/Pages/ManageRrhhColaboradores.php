<?php

namespace App\Filament\Resources\RrhhColaboradores\Pages;

use App\Filament\Resources\RrhhColaboradores\RrhhColaboradorResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ManageRrhhColaboradores extends ManageRecords
{
    protected static string $resource = RrhhColaboradorResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Explora el directorio de colaboradores y accede al detalle de cada registro.';
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
