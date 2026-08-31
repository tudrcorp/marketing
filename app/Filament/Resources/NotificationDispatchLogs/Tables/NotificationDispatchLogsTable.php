<?php

namespace App\Filament\Resources\NotificationDispatchLogs\Tables;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\NotificationDispatchLog;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;

class NotificationDispatchLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Historial de envíos')
            ->description('Cada registro explica qué pasó, por qué y cómo resolverlo.')
            ->defaultSort('logged_at', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordClasses(fn (NotificationDispatchLog $record): string => $record->requiresAttention()
                ? 'marketing-broker-table__row marketing-dispatch-log-row--attention'
                : 'marketing-broker-table__row')
            ->columns([
                TextColumn::make('logged_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->width('9rem'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NotificationDispatchStatus::from($state)->getLabel())
                    ->color(fn (string $state): string => NotificationDispatchStatus::from($state)->getColor())
                    ->icon(fn (string $state) => NotificationDispatchStatus::from($state)->getIcon())
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => NotificationDispatchSource::from($state)->getLabel())
                    ->color(fn (string $state): string => NotificationDispatchSource::from($state)->getColor())
                    ->toggleable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) {
                            return '—';
                        }

                        return BirthdayNotificationChannel::from($state)->getLabel();
                    })
                    ->color(fn (?string $state): string => filled($state)
                        ? BirthdayNotificationChannel::from($state)->getColor()
                        : 'gray'),
                TextColumn::make('title')
                    ->label('Campaña')
                    ->searchable()
                    ->weight(FontWeight::SemiBold)
                    ->limit(36)
                    ->tooltip(fn (NotificationDispatchLog $record): string => $record->title),
                TextColumn::make('analyst_message')
                    ->label('Qué pasó')
                    ->limit(55)
                    ->tooltip(fn (NotificationDispatchLog $record): ?string => $record->analyst_message)
                    ->color(fn (NotificationDispatchLog $record): string => $record->requiresAttention() ? 'danger' : 'gray'),
                TextColumn::make('sent_count')
                    ->label('Envío')
                    ->formatStateUsing(fn (NotificationDispatchLog $record): string => $record->total_count > 0
                        ? "{$record->sent_count}/{$record->total_count}"
                        : ($record->recipient ?? '—'))
                    ->alignCenter()
                    ->toggleable(),
                TextColumn::make('sentBy.name')
                    ->label('Analista')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(NotificationDispatchStatus::class),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options(NotificationDispatchSource::class),
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options(BirthdayNotificationChannel::class),
                SelectFilter::make('attention')
                    ->label('Requiere atención')
                    ->options([
                        'yes' => 'Sí',
                    ])
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereIn('status', [
                            NotificationDispatchStatus::Failed->value,
                            NotificationDispatchStatus::Partial->value,
                            NotificationDispatchStatus::Skipped->value,
                        ])
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver detalle'),
            ]);
    }
}
