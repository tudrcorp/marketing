<?php

namespace App\Filament\Resources\CorporateEvents\Tables;

use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventStatus;
use App\Marketing\CorporateEventType;
use App\Models\CorporateEvent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CorporateEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Eventos corporativos')
            ->description('Seminarios, capacitaciones, reuniones públicas y actividades de captación TDG.')
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Evento')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->limit(40)
                    ->tooltip(fn (CorporateEvent $record): string => $record->title),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CorporateEventType::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn (?string $state): string => CorporateEventType::tryFrom((string) $state)?->getColor() ?? 'gray'),
                TextColumn::make('modality')
                    ->label('Modalidad')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CorporateEventModality::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn (?string $state): string => CorporateEventModality::tryFrom((string) $state)?->getColor() ?? 'gray'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CorporateEventStatus::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn (?string $state): string => CorporateEventStatus::tryFrom((string) $state)?->getColor() ?? 'gray')
                    ->icon(fn (?string $state): ?Heroicon => CorporateEventStatus::tryFrom((string) $state)?->getIcon()),
                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon(Heroicon::OutlinedClock),
                TextColumn::make('registrations_count')
                    ->label('Inscritos')
                    ->numeric()
                    ->sortable()
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->formatStateUsing(function (CorporateEvent $record, mixed $state): string {
                        if ($record->hasCapacity()) {
                            return "{$state}/{$record->capacity}";
                        }

                        return (string) $state;
                    }),
                TextColumn::make('venue_name')
                    ->label('Sede')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->recordUrl(fn (CorporateEvent $record): string => CorporateEventResource::getUrl('view', ['record' => $record]))
            ->searchable()
            ->searchPlaceholder('Buscar por título o sede…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(CorporateEventStatus::class),
                SelectFilter::make('event_type')
                    ->label('Tipo')
                    ->options(CorporateEventType::class),
                SelectFilter::make('modality')
                    ->label('Modalidad')
                    ->options(CorporateEventModality::class),
            ])
            ->emptyStateHeading('Sin eventos corporativos')
            ->emptyStateDescription('Crea el primer evento para comenzar a planificar actividades TDG.')
            ->emptyStateIcon(Heroicon::OutlinedCalendar)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
