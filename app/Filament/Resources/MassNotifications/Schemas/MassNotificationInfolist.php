<?php

namespace App\Filament\Resources\MassNotifications\Schemas;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MassNotificationContentType;
use App\Models\MassNotification;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MassNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Resumen de la notificación')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Encabezado')
                                    ->icon(Heroicon::OutlinedMegaphone)
                                    ->columnSpanFull(),
                                TextEntry::make('channels')
                                    ->label('Canales')
                                    ->badge()
                                    ->formatStateUsing(function (mixed $state): string {
                                        $channel = BirthdayNotificationChannel::tryFrom((string) $state);

                                        return $channel?->getLabel() ?? (string) $state;
                                    })
                                    ->separator(', '),
                                TextEntry::make('audiences')
                                    ->label('Grupos destinatarios')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(function (mixed $state): string {
                                        $audience = BirthdayNotificationAudience::tryFrom((string) $state);

                                        return $audience?->getLabel() ?? (string) $state;
                                    })
                                    ->limitList(3)
                                    ->expandableLimitedList(),
                                TextEntry::make('recipient_ids')
                                    ->label('Destinatarios específicos')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn (mixed $state): string => 'ID '.$state)
                                    ->visible(fn (MassNotification $record): bool => filled($record->recipient_ids))
                                    ->limitList(5)
                                    ->expandableLimitedList(),
                                TextEntry::make('content_type')
                                    ->label('Tipo de envío adicional')
                                    ->badge()
                                    ->formatStateUsing(function (mixed $state): string {
                                        $contentType = MassNotificationContentType::tryFromMixed($state);

                                        return $contentType?->getLabel() ?? (blank($state) ? 'Sin adjunto' : (string) $state);
                                    }),
                                TextEntry::make('createdBy.name')
                                    ->label('Creado por')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->placeholder('—'),
                                TextEntry::make('created_at')
                                    ->label('Fecha de creación')
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
                Section::make('Copy del mensaje')
                    ->schema([
                        TextEntry::make('copy')
                            ->hiddenLabel()
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make('Adjunto')
                    ->visible(fn (MassNotification $record): bool => filled($record->attachment))
                    ->schema([
                        ImageEntry::make('attachment')
                            ->hiddenLabel()
                            ->disk('public')
                            ->visibility('public')
                            ->visible(fn (MassNotification $record): bool => $record->contentTypeEnum() === MassNotificationContentType::Image),
                        TextEntry::make('attachment')
                            ->hiddenLabel()
                            ->icon(Heroicon::OutlinedPaperClip)
                            ->visible(fn (MassNotification $record): bool => $record->contentTypeEnum() !== MassNotificationContentType::Image),
                    ]),
                Section::make('Resultado del envío')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->description('Progreso consolidado, pruebas y detalle de cada disparo de esta campaña.')
                    ->schema([
                        ViewEntry::make('delivery_summary')
                            ->hiddenLabel()
                            ->view('filament.infolists.mass-notification-delivery-summary')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
