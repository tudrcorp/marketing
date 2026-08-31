<?php

namespace App\Filament\Resources\RrhhColaboradores\Tables;

use App\Filament\Actions\SendMassNotificationBulkAction;
use App\Filament\Resources\RrhhColaboradores\RrhhColaboradorResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Filament\Resources\RrhhColaboradores\Support\RrhhColaboradorPresentation;
use App\Services\Marketing\MarketingRrhhColaboradoresApiService;
use App\Support\NamePresentation;
use Filament\Actions\Action;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RrhhColaboradoresTable
{
    public static function configure(Table $table): Table
    {
        ArrayRecord::keyName('id');

        return $table
            ->heading('Directorio de colaboradores')
            ->description('Listado sincronizado desde el API de marketing. Haz clic en una fila para ver el detalle completo.')
            ->records(function (
                int $page,
                int $recordsPerPage,
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ) {
                return app(MarketingRrhhColaboradoresApiService::class)->paginate(
                    page: $page,
                    perPage: $recordsPerPage,
                    search: $search,
                    sortColumn: $sortColumn ?? 'fullName',
                    sortDirection: $sortDirection ?? 'asc',
                );
            })
            ->defaultSort('fullName', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->fontFamily(FontFamily::Mono)
                    ->alignCenter()
                    ->width('4.5rem'),
                TextColumn::make('fullName')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                    ->wrap()
                    ->lineClamp(2)
                    ->tooltip(fn (array $record): ?string => NamePresentation::uppercase($record['fullName'] ?? null)),
                TextColumn::make('sexo')
                    ->label('Sexo')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => RrhhColaboradorPresentation::sexLabel($state))
                    ->color(fn (?string $state): string => RrhhColaboradorPresentation::sexColor($state)),
                TextColumn::make('fechaNacimiento')
                    ->label('Fecha de nacimiento')
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::formatDate($state))
                    ->placeholder('—'),
                TextColumn::make('fechaIngreso')
                    ->label('Fecha de ingreso')
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::formatDate($state))
                    ->placeholder('—'),
                TextColumn::make('age')
                    ->label('Edad')
                    ->alignCenter()
                    ->fontFamily(FontFamily::Mono)
                    ->formatStateUsing(fn (mixed $state): ?string => RrhhColaboradorPresentation::formatAge($state))
                    ->placeholder('—'),
                TextColumn::make('telefonoCorporativo')
                    ->label('Tel. corporativo')
                    ->icon(Heroicon::OutlinedPhone)
                    ->iconColor('primary')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => RrhhColaboradorPresentation::phoneUrl($state))
                    ->placeholder('—'),
                TextColumn::make('emailCorporativo')
                    ->label('Correo corporativo')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->iconColor('primary')
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                    ->url(fn (?string $state): ?string => filled(RrhhColaboradorPresentation::displayEmail($state)) ? 'mailto:'.RrhhColaboradorPresentation::displayEmail($state) : null)
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->limit(28)
                    ->tooltip(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state)),
                TextColumn::make('telefono')
                    ->label('Teléfono personal')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (?string $state): ?string => RrhhColaboradorPresentation::phoneUrl($state))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('emailPersonal')
                    ->label('Correo personal')
                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('emailAlternativo')
                    ->label('Correo alternativo')
                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por nombre, correo o teléfono…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->recordActions([
                Action::make('view')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (array $record): string => RrhhColaboradorResource::getUrl('view', ['record' => $record['id']])),
            ])
            ->recordActionsColumnLabel('Acciones')
            ->recordAction(null)
            ->recordUrl(fn (array $record): string => RrhhColaboradorResource::getUrl('view', ['record' => $record['id']]))
            ->groupedBulkActions([
                SendMassNotificationBulkAction::make(BirthdayNotificationAudience::Collaborators),
            ])
            ->emptyStateHeading('Sin colaboradores')
            ->emptyStateDescription('No se encontraron registros en el API de marketing. Verifica la conexión o ajusta los filtros de búsqueda.')
            ->emptyStateIcon(Heroicon::OutlinedUserGroup);
    }
}
