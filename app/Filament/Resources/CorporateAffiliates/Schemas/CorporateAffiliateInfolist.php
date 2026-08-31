<?php

namespace App\Filament\Resources\CorporateAffiliates\Schemas;

use App\Support\AffiliatePresentation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class CorporateAffiliateInfolist
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
                                TextEntry::make('first_name')
                                    ->hiddenLabel()
                                    ->size(TextSize::Large)
                                    ->weight('semibold')
                                    ->color('primary')
                                    ->columnSpan(['md' => 7]),
                                TextEntry::make('sex')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => AffiliatePresentation::sexLabel($state))
                                    ->color(fn (?string $state): string => AffiliatePresentation::sexColor($state))
                                    ->columnSpan(['md' => 2]),
                                TextEntry::make('id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('gray')
                                    ->fontFamily(FontFamily::Mono)
                                    ->prefix('ID ')
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('nro_identificacion')
                                    ->label('Identificación')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->fontFamily(FontFamily::Mono)
                                    ->copyable()
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('age')
                                    ->label('Edad')
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->fontFamily(FontFamily::Mono)
                                    ->formatStateUsing(fn (mixed $state): ?string => AffiliatePresentation::formatAge($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('birth_date')
                                    ->label('Fecha de nacimiento')
                                    ->icon(Heroicon::OutlinedCake)
                                    ->fontFamily(FontFamily::Mono)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('corporateAffiliateTabs')
                    ->contained()
                    ->persistTabInQueryString('afiliado-corporativo-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedBuildingOffice)
                            ->schema([
                                Section::make('Datos del afiliado')
                                    ->description('Información de identificación del afiliado corporativo.')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('first_name')
                                                    ->label('Nombre')
                                                    ->columnSpanFull(),
                                                TextEntry::make('nro_identificacion')
                                                    ->label('Número de identificación')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->copyable()
                                                    ->placeholder('—'),
                                                TextEntry::make('birth_date')
                                                    ->label('Fecha de nacimiento')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->placeholder('—'),
                                                TextEntry::make('sex')
                                                    ->label('Sexo')
                                                    ->badge()
                                                    ->formatStateUsing(fn (?string $state): string => AffiliatePresentation::sexLabel($state))
                                                    ->color(fn (?string $state): string => AffiliatePresentation::sexColor($state)),
                                                TextEntry::make('age')
                                                    ->label('Edad')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (mixed $state): ?string => AffiliatePresentation::formatAge($state))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contacto')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Medios de contacto')
                                    ->description('Información de contacto del afiliado corporativo.')
                                    ->icon(Heroicon::OutlinedAtSymbol)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('email')
                                                    ->label('Correo electrónico')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->formatStateUsing(fn (?string $state): ?string => AffiliatePresentation::displayEmail($state))
                                                    ->url(fn (?string $state): ?string => filled(AffiliatePresentation::displayEmail($state)) ? 'mailto:'.AffiliatePresentation::displayEmail($state) : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('phone')
                                                    ->label('Teléfono')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => AffiliatePresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
