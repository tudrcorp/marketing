<?php

namespace App\Filament\Resources\MassNotifications\Tables;

use App\Filament\Resources\MassNotifications\MassNotificationResource;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MassNotificationContentType;
use App\Models\MassNotification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MassNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Notificaciones masivas')
            ->description('Campañas configuradas por el analista para WhatsApp, correo y SMS.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Encabezado')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->icon(Heroicon::OutlinedMegaphone)
                    ->limit(40)
                    ->tooltip(fn (MassNotification $record): string => $record->title),
                TextColumn::make('channels')
                    ->label('Canales')
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        $channel = BirthdayNotificationChannel::tryFrom((string) $state);

                        return $channel?->getLabel() ?? (string) $state;
                    })
                    ->color(function (mixed $state): string {
                        return BirthdayNotificationChannel::tryFrom((string) $state)?->getColor() ?? 'gray';
                    })
                    ->separator(', '),
                TextColumn::make('audiences')
                    ->label('Grupos')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(function (mixed $state): string {
                        $audience = BirthdayNotificationAudience::tryFrom((string) $state);

                        return $audience?->getLabel() ?? (string) $state;
                    })
                    ->limitList(2)
                    ->expandableLimitedList(),
                TextColumn::make('content_type')
                    ->label('Tipo adicional')
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        $contentType = MassNotificationContentType::tryFromMixed($state);

                        return $contentType?->getLabel() ?? (blank($state) ? 'Sin adjunto' : (string) $state);
                    })
                    ->icon(function (mixed $state): ?Heroicon {
                        $contentType = MassNotificationContentType::tryFromMixed($state);

                        return $contentType?->getIcon() ?? (blank($state) ? Heroicon::OutlinedMinusCircle : null);
                    })
                    ->color(function (mixed $state): string {
                        return MassNotificationContentType::tryFromMixed($state)?->getColor() ?? 'gray';
                    }),
                TextColumn::make('copy')
                    ->label('Copy')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (MassNotification $record): string => $record->copy)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->icon(Heroicon::OutlinedUser)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->striped()
            ->recordClasses('marketing-broker-table__row')
            ->searchable()
            ->searchPlaceholder('Buscar por encabezado o copy…')
            ->searchDebounce('400ms')
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->filters([
                SelectFilter::make('channels')
                    ->label('Canal')
                    ->options(BirthdayNotificationChannel::class)
                    ->query(function ($query, array $data) {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereJsonContains('channels', $data['value']);
                    }),
                SelectFilter::make('content_type')
                    ->label('Tipo de envío')
                    ->options(MassNotificationContentType::class),
                SelectFilter::make('audiences')
                    ->label('Grupo')
                    ->options(BirthdayNotificationAudience::class)
                    ->query(function ($query, array $data) {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereJsonContains('audiences', $data['value']);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->recordUrl(fn (MassNotification $record): string => MassNotificationResource::getUrl('view', ['record' => $record]))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin notificaciones masivas')
            ->emptyStateDescription('Crea la primera campaña para enviar mensajes por WhatsApp, correo o SMS.')
            ->emptyStateIcon(Heroicon::OutlinedBellAlert);
    }
}
