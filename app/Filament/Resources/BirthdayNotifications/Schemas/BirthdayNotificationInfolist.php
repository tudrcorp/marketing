<?php

namespace App\Filament\Resources\BirthdayNotifications\Schemas;

use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;
use App\Models\BirthdayNotification;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class BirthdayNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->extraAttributes(['class' => 'marketing-broker-profile marketing-birthday-profile'])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 12])
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->size(TextSize::Large)
                                    ->weight('semibold')
                                    ->color('primary')
                                    ->columnSpan(['md' => 7]),
                                TextEntry::make('readiness_status')
                                    ->hiddenLabel()
                                    ->state(fn (BirthdayNotification $record): string => BirthdayNotificationPresentation::readinessLabel($record))
                                    ->badge()
                                    ->color(fn (BirthdayNotification $record): string => BirthdayNotificationPresentation::readinessColor($record))
                                    ->columnSpan(['md' => 2]),
                                TextEntry::make('id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('gray')
                                    ->prefix('ID ')
                                    ->columnSpan(['md' => 3]),
                                ViewEntry::make('hero_stats')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.birthday-notification-hero-stats')
                                    ->columnSpanFull(),
                                TextEntry::make('createdBy.name')
                                    ->label('Creado por')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('created_at')
                                    ->label('Fecha de creación')
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->dateTime('d/m/Y H:i')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('updated_at')
                                    ->label('Última actualización')
                                    ->icon(Heroicon::OutlinedClock)
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('birthdayNotificationTabs')
                    ->contained()
                    ->persistTabInQueryString('notificacion-cumpleanos-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs marketing-birthday-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedCake)
                            ->schema([
                                Section::make('Estado de la plantilla')
                                    ->description('Verifica que el mensaje esté listo antes de programar envíos.')
                                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                                    ->schema([
                                        ViewEntry::make('readiness')
                                            ->hiddenLabel()
                                            ->view('filament.infolists.birthday-notification-readiness')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Datos de la notificación')
                                    ->description('Información general de la plantilla configurada.')
                                    ->icon(Heroicon::OutlinedTag)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('Nombre interno')
                                                    ->icon(Heroicon::OutlinedBookmark)
                                                    ->columnSpanFull(),
                                                TextEntry::make('createdBy.name')
                                                    ->label('Creado por')
                                                    ->icon(Heroicon::OutlinedUser)
                                                    ->placeholder('—'),
                                                TextEntry::make('created_at')
                                                    ->label('Fecha de creación')
                                                    ->icon(Heroicon::OutlinedCalendarDays)
                                                    ->dateTime('d/m/Y H:i'),
                                                TextEntry::make('updated_at')
                                                    ->label('Última actualización')
                                                    ->icon(Heroicon::OutlinedClock)
                                                    ->dateTime('d/m/Y H:i')
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contenido')
                            ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                            ->badge(fn (BirthdayNotification $record): ?string => filled($record->copy)
                                ? (string) mb_strlen($record->copy)
                                : null)
                            ->schema([
                                Section::make('Vista previa del mensaje')
                                    ->description('Así verán el contenido los destinatarios en sus dispositivos.')
                                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                                    ->schema([
                                        ViewEntry::make('message_preview')
                                            ->hiddenLabel()
                                            ->view('filament.infolists.birthday-notification-message-preview')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Texto del mensaje')
                                    ->description('Contenido completo tal como se guardó en la plantilla.')
                                    ->icon(Heroicon::OutlinedDocumentText)
                                    ->schema([
                                        TextEntry::make('copy')
                                            ->hiddenLabel()
                                            ->prose()
                                            ->placeholder('Sin texto configurado.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Distribución')
                            ->icon(Heroicon::OutlinedPaperAirplane)
                            ->badge(fn (BirthdayNotification $record): string => (string) (
                                count($record->channelEnums()) + count($record->audienceEnums())
                            ))
                            ->schema([
                                Section::make('Alcance de la notificación')
                                    ->description('Canales de envío y grupos destinatarios seleccionados.')
                                    ->icon(Heroicon::OutlinedSignal)
                                    ->schema([
                                        ViewEntry::make('distribution')
                                            ->hiddenLabel()
                                            ->view('filament.infolists.birthday-notification-distribution')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
