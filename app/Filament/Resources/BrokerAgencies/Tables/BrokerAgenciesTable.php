<?php

namespace App\Filament\Resources\BrokerAgencies\Tables;

use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\BrokerAgencies\BrokerAgencyResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Filament\Resources\BrokerAgencies\Support\BrokerAgencyPresentation;
use App\Services\Marketing\MarketingAgenciesApiService;
use App\Support\NamePresentation;
use Filament\Actions\Action;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrokerAgenciesTable
{
    public static function configure(Table $table): Table
    {
        ArrayRecord::keyName('id');

        return $table
            ->heading('Directorio de agencias')
            ->description('Listado sincronizado desde el API de marketing. Haz clic en una fila para ver el detalle completo.')
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ) {
                return app(MarketingAgenciesApiService::class)->paginate(
                    page: $page,
                    perPage: $recordsPerPage,
                    search: $search,
                    sortColumn: $sortColumn ?? 'name_corporative',
                    sortDirection: $sortDirection ?? 'asc',
                );
            })
            ->defaultSort('name_corporative', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono)
                    ->alignCenter()
                    ->width('4.5rem'),
                TextColumn::make('code')
                    ->label('Código TDG')
                    ->badge()
                    ->color('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Código copiado'),
                TextColumn::make('name_corporative')
                    ->label('Nombre corporativo')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn (array $record): ?string => NamePresentation::uppercase($record['name_corporative'] ?? null)),
                TextColumn::make('ci_responsable')
                    ->label('CI responsable')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('CI copiado'),
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
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => BrokerAgencyPresentation::phoneUrl($state))
                    ->placeholder('—'),
                TextColumn::make('agency_type_id')
                    ->label('Tipo de agencia')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => BrokerAgencyPresentation::agencyTypeLabel($state))
                    ->color(fn (?string $state): string => BrokerAgencyPresentation::agencyTypeColor($state)),
                TextColumn::make('rif')
                    ->label('RIF')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre, código, correo o teléfono…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => BrokerAgencyResource::getUrl('view', ['record' => $record['id']])),
            ])
            ->recordActionsColumnLabel('Acciones')
            ->recordAction(null)
            ->recordUrl(fn (array $record): string => BrokerAgencyResource::getUrl('view', ['record' => $record['id']]))
            ->groupedBulkActions([
                SendMassNotificationBulkAction::make(BirthdayNotificationAudience::BrokerAgencies),
            ])
            ->emptyStateHeading('Sin agencias de corretaje')
            ->emptyStateDescription('No se encontraron registros en el API de marketing. Verifica la conexión o ajusta los filtros de búsqueda.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2);
    }
}
