<?php

namespace App\Filament\Resources\IndividualAffiliates\Tables;

use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\IndividualAffiliates\IndividualAffiliateResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Services\Marketing\MarketingIndividualAffiliatesApiService;
use App\Support\AffiliatePresentation;
use App\Support\NamePresentation;
use Filament\Actions\Action;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IndividualAffiliatesTable
{
    public static function configure(Table $table): Table
    {
        ArrayRecord::keyName('id');

        return $table
            ->heading('Directorio de afiliados individuales')
            ->description('Listado sincronizado desde el API de marketing. Haz clic en una fila para ver el detalle completo.')
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ) {
                return app(MarketingIndividualAffiliatesApiService::class)->paginate(
                    page: $page,
                    perPage: $recordsPerPage,
                    search: $search,
                    sortColumn: $sortColumn ?? 'full_name',
                    sortDirection: $sortDirection ?? 'asc',
                );
            })
            ->defaultSort('full_name', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono)
                    ->alignCenter()
                    ->width('4.5rem'),
                TextColumn::make('full_name')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn (array $record): ?string => NamePresentation::uppercase($record['full_name'] ?? null)),
                TextColumn::make('nro_identificacion')
                    ->label('Identificación')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Identificación copiada'),
                TextColumn::make('birth_date')
                    ->label('Fecha de nacimiento')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—'),
                TextColumn::make('sex')
                    ->label('Sexo')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => AffiliatePresentation::sexLabel($state))
                    ->color(fn (?string $state): string => AffiliatePresentation::sexColor($state)),
                TextColumn::make('age')
                    ->label('Edad')
                    ->alignCenter()
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (mixed $state): ?string => AffiliatePresentation::formatAge($state))
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => AffiliatePresentation::phoneUrl($state))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->formatStateUsing(fn (?string $state): ?string => AffiliatePresentation::displayEmail($state))
                    ->url(fn (?string $state): ?string => filled(AffiliatePresentation::displayEmail($state)) ? 'mailto:'.AffiliatePresentation::displayEmail($state) : null)
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->limit(28)
                    ->tooltip(fn (?string $state): ?string => AffiliatePresentation::displayEmail($state)),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre, identificación o correo…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => IndividualAffiliateResource::getUrl('view', ['record' => $record['id']])),
            ])
            ->recordActionsColumnLabel('Acciones')
            ->recordAction(null)
            ->recordUrl(fn (array $record): string => IndividualAffiliateResource::getUrl('view', ['record' => $record['id']]))
            ->groupedBulkActions([
                SendMassNotificationBulkAction::make(BirthdayNotificationAudience::IndividualAffiliates),
            ])
            ->emptyStateHeading('Sin afiliados individuales')
            ->emptyStateDescription('No se encontraron registros en el API de marketing.')
            ->emptyStateIcon(Heroicon::OutlinedUser);
    }
}
