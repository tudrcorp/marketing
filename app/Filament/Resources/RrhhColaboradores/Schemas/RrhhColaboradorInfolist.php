<?php

namespace App\Filament\Resources\RrhhColaboradores\Schemas;

use App\Filament\Resources\RrhhColaboradores\Support\RrhhColaboradorPresentation;
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

class RrhhColaboradorInfolist
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
                                TextEntry::make('fullName')
                                    ->hiddenLabel()
                                    ->size(TextSize::Large)
                                    ->weight('semibold')
                                    ->color('primary')
                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                    ->columnSpan(['md' => 7]),
                                TextEntry::make('sexo')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => RrhhColaboradorPresentation::sexLabel($state))
                                    ->color(fn (?string $state): string => RrhhColaboradorPresentation::sexColor($state))
                                    ->columnSpan(['md' => 2]),
                                TextEntry::make('id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('gray')
                                    ->fontFamily(FontFamily::Mono)
                                    ->prefix('ID ')
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('fechaIngreso')
                                    ->label('Fecha de ingreso')
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->fontFamily(FontFamily::Mono)
                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::formatDate($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('emailCorporativo')
                                    ->label('Correo corporativo')
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->iconColor('primary')
                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                                    ->url(fn (?string $state): ?string => filled(RrhhColaboradorPresentation::displayEmail($state)) ? 'mailto:'.RrhhColaboradorPresentation::displayEmail($state) : null)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('telefonoCorporativo')
                                    ->label('Teléfono corporativo')
                                    ->icon(Heroicon::OutlinedPhone)
                                    ->iconColor('primary')
                                    ->fontFamily(FontFamily::Mono)
                                    ->url(fn (?string $state): ?string => RrhhColaboradorPresentation::phoneUrl($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('rrhhColaboradorTabs')
                    ->contained()
                    ->persistTabInQueryString('colaborador-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedUser)
                            ->schema([
                                Section::make('Datos personales')
                                    ->description('Información de identificación y antigüedad del colaborador.')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('fullName')
                                                    ->label('Nombre completo')
                                                    ->formatStateUsing(fn (?string $state): ?string => NamePresentation::uppercase($state))
                                                    ->columnSpanFull(),
                                                TextEntry::make('sexo')
                                                    ->label('Sexo')
                                                    ->badge()
                                                    ->formatStateUsing(fn (?string $state): string => RrhhColaboradorPresentation::sexLabel($state))
                                                    ->color(fn (?string $state): string => RrhhColaboradorPresentation::sexColor($state)),
                                                TextEntry::make('fechaNacimiento')
                                                    ->label('Fecha de nacimiento')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::formatDate($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('age')
                                                    ->label('Edad')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (mixed $state): ?string => RrhhColaboradorPresentation::formatAge($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('fechaIngreso')
                                                    ->label('Fecha de ingreso')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::formatDate($state))
                                                    ->placeholder('—')
                                                    ->columnSpan(2),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contacto')
                            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                            ->schema([
                                Section::make('Medios de contacto')
                                    ->description('Teléfonos y correos registrados del colaborador.')
                                    ->icon(Heroicon::OutlinedAtSymbol)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('telefonoCorporativo')
                                                    ->label('Teléfono corporativo')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => RrhhColaboradorPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('telefono')
                                                    ->label('Teléfono personal')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => RrhhColaboradorPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('emailCorporativo')
                                                    ->label('Correo corporativo')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                                                    ->url(fn (?string $state): ?string => filled(RrhhColaboradorPresentation::displayEmail($state)) ? 'mailto:'.RrhhColaboradorPresentation::displayEmail($state) : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('emailPersonal')
                                                    ->label('Correo personal')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                                                    ->url(fn (?string $state): ?string => filled(RrhhColaboradorPresentation::displayEmail($state)) ? 'mailto:'.RrhhColaboradorPresentation::displayEmail($state) : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('emailAlternativo')
                                                    ->label('Correo alternativo')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->formatStateUsing(fn (?string $state): ?string => RrhhColaboradorPresentation::displayEmail($state))
                                                    ->url(fn (?string $state): ?string => filled(RrhhColaboradorPresentation::displayEmail($state)) ? 'mailto:'.RrhhColaboradorPresentation::displayEmail($state) : null)
                                                    ->placeholder('—')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
