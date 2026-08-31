<?php

namespace App\Filament\Resources\TravelAgencies\Schemas;

use App\Filament\Resources\TravelAgencies\Support\TravelAgencyPresentation;
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

class TravelAgencyInfolist
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
                                    ->columnSpan(['md' => 6]),
                                TextEntry::make('typeIdentification')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => TravelAgencyPresentation::identificationTypeLabel($state))
                                    ->color(fn (?string $state): string => TravelAgencyPresentation::identificationTypeColor($state))
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('gray')
                                    ->fontFamily(FontFamily::Mono)
                                    ->prefix('ID ')
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('representante')
                                    ->label('Representante')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('numberIdentification')
                                    ->label('Identificación')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->fontFamily(FontFamily::Mono)
                                    ->copyable()
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('fechaIngreso')
                                    ->label('Fecha de ingreso')
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->fontFamily(FontFamily::Mono)
                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgencyPresentation::formatDate($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('travelAgencyTabs')
                    ->contained()
                    ->persistTabInQueryString('agencia-viaje-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedBuildingStorefront)
                            ->schema([
                                Section::make('Datos de la agencia')
                                    ->description('Información de registro y representante legal.')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('fechaIngreso')
                                                    ->label('Fecha de ingreso')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgencyPresentation::formatDate($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('FechaNacimientoRepresentante')
                                                    ->label('Nacimiento del representante')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (?string $state): ?string => TravelAgencyPresentation::formatDate($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('age')
                                                    ->label('Edad del representante')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (mixed $state): ?string => TravelAgencyPresentation::formatAge($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('typeIdentification')
                                                    ->label('Tipo de identificación')
                                                    ->badge()
                                                    ->formatStateUsing(fn (?string $state): string => TravelAgencyPresentation::identificationTypeLabel($state))
                                                    ->color(fn (?string $state): string => TravelAgencyPresentation::identificationTypeColor($state)),
                                                TextEntry::make('numberIdentification')
                                                    ->label('Número de identificación')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->copyable()
                                                    ->placeholder('—')
                                                    ->columnSpan(2),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contacto')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Medios de contacto')
                                    ->description('Información principal para comunicación con la agencia.')
                                    ->icon(Heroicon::OutlinedAtSymbol)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('email')
                                                    ->label('Correo electrónico')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->url(fn (?string $state): ?string => filled($state) ? "mailto:{$state}" : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('phone')
                                                    ->label('Teléfono')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => TravelAgencyPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('phoneAdditional')
                                                    ->label('Teléfono adicional')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => TravelAgencyPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('userInstagram')
                                                    ->label('Instagram')
                                                    ->icon(Heroicon::OutlinedCamera)
                                                    ->iconColor('primary')
                                                    ->url(fn (?string $state): ?string => TravelAgencyPresentation::instagramUrl($state))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
