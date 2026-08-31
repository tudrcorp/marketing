<?php

namespace App\Filament\Resources\MarketingRoles\Tables;

use App\Models\MarketingRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketingRolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Roles y permisos')
            ->description('Define perfiles de acceso para analistas, aprobadores y visitantes autorizados.')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Rol')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('permissions')
                    ->label('Permisos')
                    ->getStateUsing(fn (MarketingRole $record): int => count($record->permissions ?? []))
                    ->suffix(' permisos')
                    ->alignCenter(),
                TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->alignCenter(),
                IconColumn::make('is_system')
                    ->label('Sistema')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }
}
