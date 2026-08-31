<?php

namespace App\Filament\Resources\TravelAgents\Tables;

use App\Filament\Resources\TravelAgents\Support\TravelAgentPresentation;
use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\TravelAgents\TravelAgentResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Services\Marketing\MarketingTravelAgentsApiService;
use App\Support\NamePresentation;
use Filament\Actions\Action;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TravelAgentsTable
{
    public static function configure(Table $table): Table
    {
        ArrayRecord::keyName('id');

        return $table
            ->heading('Directorio de agentes de viajes')
            ->description('Listado sincronizado desde el API de marketing. Haz clic en una fila para ver el detalle completo.')
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ) {
                return app(MarketingTravelAgentsApiService::class)->paginate(
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
                TextColumn::make('fechaNacimiento')
                    ->label('Fecha de nacimiento')
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (?string $state): ?string => TravelAgentPresentation::formatBirthDate($state))
                    ->placeholder('—'),
                TextColumn::make('age')
                    ->label('Edad')
                    ->alignCenter()
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (mixed $state): ?string => TravelAgentPresentation::formatAge($state))
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => TravelAgentPresentation::phoneUrl($state))
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->formatStateUsing(fn (?string $state): ?string => TravelAgentPresentation::displayEmail($state))
                    ->url(fn (?string $state): ?string => filled(TravelAgentPresentation::displayEmail($state)) ? 'mailto:'.TravelAgentPresentation::displayEmail($state) : null)
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->limit(28)
                    ->tooltip(fn (?string $state): ?string => TravelAgentPresentation::displayEmail($state)),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre, correo o teléfono…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => TravelAgentResource::getUrl('view', ['record' => $record['id']])),
            ])
            ->recordActionsColumnLabel('Acciones')
            ->recordAction(null)
            ->recordUrl(fn (array $record): string => TravelAgentResource::getUrl('view', ['record' => $record['id']]))
            ->groupedBulkActions([
                SendMassNotificationBulkAction::make(BirthdayNotificationAudience::TravelAgents),
            ])
            ->emptyStateHeading('Sin agentes de viajes')
            ->emptyStateDescription('No se encontraron registros en el API de marketing. Verifica la conexión o ajusta los filtros de búsqueda.')
            ->emptyStateIcon(Heroicon::OutlinedPaperAirplane);
    }
}
