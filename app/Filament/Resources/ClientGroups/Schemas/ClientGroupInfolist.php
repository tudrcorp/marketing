<?php

namespace App\Filament\Resources\ClientGroups\Schemas;

use App\Models\ClientGroup;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class ClientGroupInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Grupo')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre')
                            ->weight(FontWeight::SemiBold)
                            ->icon(Heroicon::OutlinedUserGroup),
                        TextEntry::make('color')
                            ->label('Color')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => $state)
                            ->color(fn (string $state): string => 'gray'),
                        TextEntry::make('clients_count')
                            ->label('Clientes registrados')
                            ->state(fn (ClientGroup $record): int => $record->clients()->count())
                            ->icon(Heroicon::OutlinedUsers),
                    ])
                    ->columns(3),
                Section::make('Responsable')
                    ->description('Contacto al que se enrutan las respuestas de WhatsApp y SMS de este grupo.')
                    ->schema([
                        TextEntry::make('responsible_name')
                            ->label('Nombre y apellido')
                            ->icon(Heroicon::OutlinedUser),
                        TextEntry::make('responsible_email')
                            ->label('Correo')
                            ->copyable()
                            ->icon(Heroicon::OutlinedEnvelope),
                        TextEntry::make('responsible_phone')
                            ->label('Teléfono')
                            ->copyable()
                            ->icon(Heroicon::OutlinedPhone),
                    ])
                    ->columns(3),
            ]);
    }
}
