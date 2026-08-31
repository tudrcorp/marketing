<?php

namespace App\Filament\Resources\ClientGroups\Tables;

use App\Models\ClientGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Grupos de clientes')
            ->description('Organiza clientes por grupo y define el responsable que recibirá las respuestas de notificaciones.')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Grupo')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->description(fn (ClientGroup $record): string => 'Responsable: '.$record->responsible_name),
                TextColumn::make('color')
                    ->label('Color')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state)
                    ->color(fn (string $state): string => 'gray')
                    ->extraAttributes(fn (ClientGroup $record): array => [
                        'style' => 'background-color: '.$record->color.'; color: #fff; border-color: '.$record->color,
                    ]),
                TextColumn::make('responsible_name')
                    ->label('Responsable')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('responsible_phone')
                    ->label('Tel. responsable')
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('clients_count')
                    ->label('Clientes')
                    ->counts('clients')
                    ->sortable()
                    ->icon(Heroicon::OutlinedUsers),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
