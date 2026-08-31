<?php

namespace App\Filament\Resources\LegalSuppliers\Schemas;

use App\Support\NamePresentation;
use App\Support\SupplierPresentation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class LegalSupplierInfolist
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
                                TextEntry::make('status_convenio')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color(fn (?string $state): string => SupplierPresentation::convenioColor($state))
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('status_sistema')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color(fn (?string $state): string => SupplierPresentation::sistemaColor($state))
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('rif')
                                    ->label('RIF')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->fontFamily(FontFamily::Mono)
                                    ->copyable()
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('razon_social')
                                    ->label('Razón social')
                                    ->icon(Heroicon::OutlinedBuildingOffice2)
                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('correo_principal')
                                    ->label('Correo')
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->iconColor('primary')
                                    ->formatStateUsing(fn (?string $state): ?string => SupplierPresentation::displayEmail($state))
                                    ->url(fn (?string $state): ?string => filled(SupplierPresentation::displayEmail($state)) ? 'mailto:'.SupplierPresentation::displayEmail($state) : null)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('legalSupplierTabs')
                    ->contained()
                    ->persistTabInQueryString('proveedor-juridico-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->schema([
                                Section::make('Datos del proveedor')
                                    ->description('Información de identificación y estatus del proveedor jurídico.')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('Nombre')
                                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                                    ->columnSpanFull(),
                                                TextEntry::make('razon_social')
                                                    ->label('Razón social')
                                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                                    ->placeholder('—')
                                                    ->columnSpanFull(),
                                                TextEntry::make('rif')
                                                    ->label('RIF')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->copyable()
                                                    ->placeholder('—'),
                                                TextEntry::make('status_convenio')
                                                    ->label('Convenio')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => SupplierPresentation::convenioColor($state)),
                                                TextEntry::make('status_sistema')
                                                    ->label('Estado en sistema')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => SupplierPresentation::sistemaColor($state))
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contacto')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Medios de contacto')
                                    ->description('Teléfonos y correo del proveedor.')
                                    ->icon(Heroicon::OutlinedAtSymbol)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('local_phone')
                                                    ->label('Teléfono local')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => SupplierPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('personal_phone')
                                                    ->label('Teléfono personal')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => SupplierPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('correo_principal')
                                                    ->label('Correo principal')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->formatStateUsing(fn (?string $state): ?string => SupplierPresentation::displayEmail($state))
                                                    ->url(fn (?string $state): ?string => filled(SupplierPresentation::displayEmail($state)) ? 'mailto:'.SupplierPresentation::displayEmail($state) : null)
                                                    ->placeholder('—')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
