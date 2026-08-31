<?php

namespace App\Filament\Resources\LegalSuppliers\Tables;

use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\LegalSuppliers\LegalSupplierResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Services\Marketing\MarketingLegalSuppliersApiService;
use App\Support\NamePresentation;
use App\Support\SupplierPresentation;
use Filament\Actions\Action;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalSuppliersTable
{
    public static function configure(Table $table): Table
    {
        ArrayRecord::keyName('id');

        return $table
            ->heading('Directorio de proveedores jurídicos')
            ->description('Listado sincronizado desde el API de marketing. Haz clic en una fila para ver el detalle completo.')
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ) {
                return app(MarketingLegalSuppliersApiService::class)->paginate(
                    page: $page,
                    perPage: $recordsPerPage,
                    search: $search,
                    sortColumn: $sortColumn ?? 'name',
                    sortDirection: $sortDirection ?? 'asc',
                );
            })
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('rif')
                    ->label('RIF')
                    ->badge()
                    ->color('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('RIF copiado'),
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
                TextColumn::make('razon_social')
                    ->label('Razón social')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                    ->wrap()
                    ->lineClamp(2)
                    ->placeholder('—'),
                TextColumn::make('status_convenio')
                    ->label('Convenio')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (?string $state): string => SupplierPresentation::convenioColor($state)),
                TextColumn::make('status_sistema')
                    ->label('Estado')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (?string $state): string => SupplierPresentation::sistemaColor($state))
                    ->wrap(),
                TextColumn::make('local_phone')
                    ->label('Teléfono local')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => SupplierPresentation::phoneUrl($state))
                    ->placeholder('—'),
                TextColumn::make('correo_principal')
                    ->label('Correo')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->formatStateUsing(fn (?string $state): ?string => SupplierPresentation::displayEmail($state))
                    ->url(fn (?string $state): ?string => filled(SupplierPresentation::displayEmail($state)) ? 'mailto:'.SupplierPresentation::displayEmail($state) : null)
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->limit(28)
                    ->tooltip(fn (?string $state): ?string => SupplierPresentation::displayEmail($state)),
                TextColumn::make('personal_phone')
                    ->label('Teléfono personal')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => SupplierPresentation::phoneUrl($state))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre, RIF, razón social o correo…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => LegalSupplierResource::getUrl('view', ['record' => $record['id']])),
            ])
            ->recordActionsColumnLabel('Acciones')
            ->recordAction(null)
            ->recordUrl(fn (array $record): string => LegalSupplierResource::getUrl('view', ['record' => $record['id']]))
            ->groupedBulkActions([
                SendMassNotificationBulkAction::make(BirthdayNotificationAudience::LegalSuppliers),
            ])
            ->emptyStateHeading('Sin proveedores jurídicos')
            ->emptyStateDescription('No se encontraron registros en el API de marketing. Verifica la conexión o ajusta los filtros de búsqueda.')
            ->emptyStateIcon(Heroicon::OutlinedBuildingOffice2);
    }
}
