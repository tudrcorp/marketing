<?php

namespace App\Filament\Resources\CorporateEvents\Schemas;

use App\Filament\Resources\CorporateEvents\Support\CorporateEventPresentation;
use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventType;
use App\Models\CorporateEvent;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CorporateEventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->extraAttributes(['class' => 'marketing-broker-profile marketing-corporate-event-profile'])
                    ->schema([
                        ImageEntry::make('cover_image')
                            ->label('Imagen del evento')
                            ->disk('public')
                            ->visibility('public')
                            ->visible(fn (CorporateEvent $record): bool => CorporateEventPresentation::hasCoverImage($record))
                            ->extraImgAttributes([
                                'class' => 'marketing-corporate-event-cover__image',
                            ])
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'marketing-corporate-event-cover']),
                        ViewEntry::make('profile_hero')
                            ->hiddenLabel()
                            ->view('filament.infolists.corporate-event-profile-hero')
                            ->columnSpanFull(),
                    ]),
                ViewEntry::make('registration_share')
                    ->hiddenLabel()
                    ->view('filament.infolists.corporate-event-registration-share')
                    ->columnSpanFull(),
                Tabs::make('corporateEventTabs')
                    ->contained()
                    ->persistTabInQueryString('evento-corporativo-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Detalle del evento')
                                    ->schema([
                                        TextEntry::make('event_type')
                                            ->label('Tipo')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => CorporateEventType::tryFrom((string) $state)?->getLabel() ?? (string) $state),
                                        TextEntry::make('modality')
                                            ->label('Modalidad')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => CorporateEventModality::tryFrom((string) $state)?->getLabel() ?? (string) $state),
                                        TextEntry::make('starts_at')
                                            ->label('Inicio')
                                            ->dateTime('d/m/Y H:i'),
                                        TextEntry::make('ends_at')
                                            ->label('Fin')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('summary')
                                            ->label('Resumen')
                                            ->columnSpanFull(),
                                        TextEntry::make('description')
                                            ->label('Descripción')
                                            ->markdown()
                                            ->columnSpanFull(),
                                        ImageEntry::make('cover_image')
                                            ->label('Imagen de portada')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->visible(fn (CorporateEvent $record): bool => CorporateEventPresentation::hasCoverImage($record))
                                            ->extraImgAttributes([
                                                'class' => 'marketing-corporate-event-cover__image',
                                            ])
                                            ->columnSpanFull()
                                            ->extraAttributes(['class' => 'marketing-corporate-event-cover marketing-corporate-event-cover--inline']),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Logística')
                            ->icon(Heroicon::OutlinedMapPin)
                            ->schema([
                                Section::make('Ubicación e inscripciones')
                                    ->schema([
                                        TextEntry::make('venue_name')
                                            ->label('Sede')
                                            ->placeholder('—'),
                                        TextEntry::make('venue_address')
                                            ->label('Dirección')
                                            ->placeholder('—'),
                                        TextEntry::make('virtual_url')
                                            ->label('Enlace virtual')
                                            ->url(fn (?string $state): ?string => $state)
                                            ->placeholder('—'),
                                        TextEntry::make('registration_url')
                                            ->label('URL de inscripción pública')
                                            ->url(fn (?string $state): ?string => $state)
                                            ->copyable()
                                            ->placeholder('—'),
                                        TextEntry::make('registration_deadline')
                                            ->label('Cierre de inscripciones')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('capacity')
                                            ->label('Cupo máximo')
                                            ->placeholder('Sin límite'),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Audiencias')
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->schema([
                                Section::make('Grupos destinatarios')
                                    ->schema([
                                        TextEntry::make('target_audiences')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->formatStateUsing(fn (mixed $state): string => BirthdayNotificationAudience::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                                            ->separator(', '),
                                        TextEntry::make('promoted_channels')
                                            ->label('Canales promocionados')
                                            ->badge()
                                            ->placeholder('Sin promoción')
                                            ->formatStateUsing(fn (mixed $state): string => (string) $state),
                                        TextEntry::make('promoted_at')
                                            ->label('Última promoción')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
