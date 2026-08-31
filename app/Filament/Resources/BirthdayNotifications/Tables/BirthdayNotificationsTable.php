<?php

namespace App\Filament\Resources\BirthdayNotifications\Tables;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BirthdayNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Notificaciones de cumpleaños')
            ->description('Plantillas de felicitación por canal y grupo de audiencia TDG.')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->limit(40)
                    ->tooltip(fn (BirthdayNotification $record): string => $record->name),
                TextColumn::make('channels')
                    ->label('Canales')
                    ->badge()
                    ->formatStateUsing(function (mixed $state): string {
                        $channel = BirthdayNotificationChannel::tryFrom((string) $state);

                        return $channel?->getLabel() ?? (string) $state;
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
                TextColumn::make('copy')
                    ->label('Mensaje')
                    ->limit(50)
                    ->tooltip(fn (BirthdayNotification $record): string => $record->copy)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
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
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
