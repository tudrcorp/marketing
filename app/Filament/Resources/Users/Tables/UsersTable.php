<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Usuarios del panel')
            ->description('Registra al equipo de marketing y asígnales un rol para entrar a operaciones.')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->icon(Heroicon::OutlinedUser)
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('marketingRole.name')
                    ->label('Rol')
                    ->badge()
                    ->sortable()
                    ->placeholder('Sin rol')
                    ->color(fn (User $record): string => match ($record->marketingRole?->slug) {
                        'administrador' => 'primary',
                        'analista' => 'info',
                        'aprobador' => 'warning',
                        'visor' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('marketing_role_id')
                    ->label('Rol')
                    ->relationship('marketingRole', 'name')
                    ->native(false)
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }
}
