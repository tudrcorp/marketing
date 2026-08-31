<?php

namespace App\Filament\Resources\TravelAgents\Schemas;

use App\Filament\Resources\TravelAgents\Support\TravelAgentPresentation;
use App\Support\NamePresentation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class TravelAgentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->extraAttributes(['class' => 'marketing-broker-profile'])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 12])
                            ->schema([
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->size(TextSize::Large)
                                    ->weight('semibold')
                                    ->color('primary')
                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                    ->columnSpan(['md' => 7]),
                                TextEntry::make('age')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->formatStateUsing(fn (mixed $state): ?string => TravelAgentPresentation::formatAge($state) ?? '—')
                                    ->color('info')
                                    ->columnSpan(['md' => 2]),
                                TextEntry::make('id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('gray')
                                    ->fontFamily(FontFamily::Mono)
                                    ->prefix('ID ')
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('fechaNacimiento')
                                    ->label('Fecha de nacimiento')
                                    ->icon(Heroicon::OutlinedCake)
                                    ->fontFamily(FontFamily::Mono)
                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgentPresentation::formatBirthDate($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('email')
                                    ->label('Correo')
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->iconColor('primary')
                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgentPresentation::displayEmail($state))
                                    ->url(fn (?string $state): ?string => filled(TravelAgentPresentation::displayEmail($state)) ? 'mailto:'.TravelAgentPresentation::displayEmail($state) : null)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('phone')
                                    ->label('Teléfono')
                                    ->icon(Heroicon::OutlinedPhone)
                                    ->iconColor('primary')
                                    ->fontFamily(FontFamily::Mono)
                                    ->url(fn (?string $state): ?string => TravelAgentPresentation::phoneUrl($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('travelAgentTabs')
                    ->contained()
                    ->persistTabInQueryString('agente-viaje-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedUser)
                            ->schema([
                                Section::make('Datos personales')
                                    ->description('Información de identificación del agente de viajes.')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('Nombre completo')
                                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                                    ->columnSpanFull(),
                                                TextEntry::make('fechaNacimiento')
                                                    ->label('Fecha de nacimiento')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgentPresentation::formatBirthDate($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('age')
                                                    ->label('Edad')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (mixed $state): ?string => TravelAgentPresentation::formatAge($state))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contacto')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Medios de contacto')
                                    ->description('Información principal para comunicación con el agente.')
                                    ->icon(Heroicon::OutlinedAtSymbol)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('email')
                                                    ->label('Correo electrónico')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgentPresentation::displayEmail($state))
                                                    ->url(fn (?string $state): ?string => filled(TravelAgentPresentation::displayEmail($state)) ? 'mailto:'.TravelAgentPresentation::displayEmail($state) : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('phone')
                                                    ->label('Teléfono')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => TravelAgentPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
