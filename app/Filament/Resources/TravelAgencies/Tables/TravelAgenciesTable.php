<?php

namespace App\Filament\Resources\TravelAgencies\Tables;

use App\Filament\Resources\TravelAgencies\Support\TravelAgencyPresentation;
use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\TravelAgencies\TravelAgencyResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Services\Marketing\MarketingTravelAgenciesApiService;
use App\Support\NamePresentation;
use Filament\Actions\Action;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TravelAgenciesTable
{
    public static function configure(Table $table): Table
    {
        ArrayRecord::keyName('id');

        return $table
            ->heading('Directorio de agencias de viajes')
            ->description('Listado sincronizado desde el API de marketing. Haz clic en una fila para ver el detalle completo.')
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ) {
                return app(MarketingTravelAgenciesApiService::class)->paginate(
                    page: $page,
                    perPage: $recordsPerPage,
                    search: $search,
                    sortColumn: $sortColumn ?? 'name',
                    sortDirection: $sortDirection ?? 'asc',
                );
            })
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono)
                    ->alignCenter()
                    ->width('4.5rem'),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn (array $record): ?string => NamePresentation::uppercase($record['name'] ?? null)),
                TextColumn::make('representante')
                    ->label('Representante')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('—'),
                TextColumn::make('typeIdentification')
                    ->label('Tipo ID')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => TravelAgencyPresentation::identificationTypeLabel($state))
                    ->color(fn (?string $state): string => TravelAgencyPresentation::identificationTypeColor($state)),
                TextColumn::make('numberIdentification')
                    ->label('Identificación')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Identificación copiada'),
                TextColumn::make('fechaIngreso')
                    ->label('Fecha de ingreso')
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (?string $state): ?string => TravelAgencyPresentation::formatDate($state))
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => TravelAgencyPresentation::phoneUrl($state))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->url(fn (?string $state): ?string => filled($state) ? "mailto:{$state}" : null)
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->limit(28)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('age')
                    ->label('Edad rep.')
                    ->alignCenter()
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (mixed $state): ?string => TravelAgencyPresentation::formatAge($state))
                    ->placeholder('—'),
                TextColumn::make('phoneAdditional')
                    ->label('Teléfono adicional')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => TravelAgencyPresentation::phoneUrl($state))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('userInstagram')
                    ->label('Instagram')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre, representante, correo o teléfono…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => TravelAgencyResource::getUrl('view', ['record' => $record['id']])),
            ])
            ->recordActionsColumnLabel('Acciones')
            ->recordAction(null)
            ->recordUrl(fn (array $record): string => TravelAgencyResource::getUrl('view', ['record' => $record['id']]))
            ->groupedBulkActions([
                SendMassNotificationBulkAction::make(BirthdayNotificationAudience::TravelAgencies),
            ])
            ->emptyStateHeading('Sin agencias de viajes')
            ->emptyStateDescription('No se encontraron registros en el API de marketing. Verifica la conexión o ajusta los filtros de búsqueda.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingStorefront);
    }
}
