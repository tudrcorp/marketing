<?php

namespace App\Filament\Resources\SocialAccounts\Tables;

use App\Filament\Resources\SocialAccounts\Actions\ViewAccountPasswordAction;
use App\Filament\Resources\SocialAccounts\SocialAccountResource;
use App\Marketing\SocialPlatform;
use App\Models\SocialAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SocialAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Cuentas de redes sociales')
            ->description('Directorio de perfiles oficiales TDG conectados al calendario editorial.')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->icon(Heroicon::OutlinedGlobeAlt),
                TextColumn::make('platform')
                    ->label('Red')
                    ->formatStateUsing(fn (SocialPlatform $state): string => $state->getSelectOptionHtml())
                    ->html()
                    ->sortable(),
                TextColumn::make('handle')
                    ->label('Handle')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->iconColor('primary')
                    ->searchable()
                    ->placeholder('—'),
                IconColumn::make('has_account_password')
                    ->label('Clave')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->getStateUsing(fn (SocialAccount $record): bool => $record->hasAccountPassword())
                    ->tooltip(fn (SocialAccount $record): ?string => $record->hasAccountPassword()
                        ? 'Clave guardada de forma cifrada'
                        : 'Sin clave registrada'),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                TextColumn::make('publications_count')
                    ->label('Publicaciones')
                    ->icon(Heroicon::OutlinedNewspaper)
                    ->counts('publications')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->icon(Heroicon::OutlinedClock)
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Red social')
                    ->options(SocialPlatform::class)
                    ->multiple(),
                TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->recordActions([
                ViewAccountPasswordAction::make(),
                EditAction::make(),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre o handle…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordUrl(fn (SocialAccount $record): string => SocialAccountResource::getUrl('edit', ['record' => $record]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin cuentas de redes')
            ->emptyStateDescription('Registra la primera cuenta oficial TDG para conectar el calendario editorial.')
            ->emptyStateIcon(Heroicon::OutlinedGlobeAlt);
    }
}
