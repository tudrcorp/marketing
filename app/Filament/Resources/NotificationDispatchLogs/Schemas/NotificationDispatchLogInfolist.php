<?php

namespace App\Filament\Resources\NotificationDispatchLogs\Schemas;

use App\Filament\Resources\NotificationDispatchLogs\Support\NotificationDispatchLogPresentation;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\NotificationDispatchLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class NotificationDispatchLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                ViewEntry::make('analyst_guidance')
                    ->hiddenLabel()
                    ->view('filament.infolists.notification-dispatch-log-guidance')
                    ->columnSpanFull(),
                Section::make('Contexto del envío')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->extraAttributes(['class' => 'marketing-dispatch-log__section'])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])
                            ->schema([
                                TextEntry::make('source')
                                    ->label('Origen')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => NotificationDispatchSource::from($state)->getLabel())
                                    ->color(fn (string $state): string => NotificationDispatchSource::from($state)->getColor()),
                                TextEntry::make('channel')
                                    ->label('Canal')
                                    ->badge()
                                    ->placeholder('—')
                                    ->formatStateUsing(fn (?string $state): string => filled($state)
                                        ? BirthdayNotificationChannel::from($state)->getLabel()
                                        : '—'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => NotificationDispatchStatus::from($state)->getLabel())
                                    ->color(fn (string $state): string => NotificationDispatchStatus::from($state)->getColor()),
                                TextEntry::make('title')
                                    ->label('Campaña')
                                    ->size(TextSize::Large)
                                    ->weight('semibold')
                                    ->columnSpan(['md' => 2]),
                                TextEntry::make('logged_at')
                                    ->label('Registrado')
                                    ->dateTime('d/m/Y H:i:s'),
                                TextEntry::make('recipient')
                                    ->label('Destinatario')
                                    ->placeholder('Envío masivo')
                                    ->icon(Heroicon::OutlinedUser),
                                TextEntry::make('batch_label')
                                    ->label('Lote')
                                    ->state(fn (NotificationDispatchLog $record): ?string => NotificationDispatchLogPresentation::batchLabel($record))
                                    ->placeholder('—'),
                                TextEntry::make('sentBy.name')
                                    ->label('Enviado por')
                                    ->placeholder('Sistema')
                                    ->icon(Heroicon::OutlinedUserCircle),
                            ]),
                    ]),
                Section::make('Progreso y reintento')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->description('Valida el avance de entrega, revisa cada fallo y reenvía solo a quienes no recibieron el mensaje.')
                    ->extraAttributes(['class' => 'marketing-dispatch-log__section'])
                    ->schema([
                        ViewEntry::make('dispatch_progress')
                            ->hiddenLabel()
                            ->view('filament.infolists.notification-dispatch-log-progress')
                            ->columnSpanFull(),
                    ]),
                Section::make('Detalle técnico')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->description('Respuestas estructuradas de las APIs de marketing para validación del equipo de sistemas.')
                    ->extraAttributes(['class' => 'marketing-dispatch-log__section'])
                    ->schema([
                        TextEntry::make('summary')
                            ->label('Resumen del sistema')
                            ->columnSpanFull(),
                        TextEntry::make('failure_code')
                            ->label('Código interno')
                            ->placeholder('—')
                            ->badge()
                            ->color('gray'),
                        ViewEntry::make('api_trace')
                            ->hiddenLabel()
                            ->view('filament.infolists.notification-dispatch-log-api-trace')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
