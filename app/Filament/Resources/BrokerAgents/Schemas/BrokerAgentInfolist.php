<?php

namespace App\Filament\Resources\BrokerAgents\Schemas;

use App\Filament\Resources\BrokerAgents\Support\BrokerAgentPresentation;
use App\Models\BrokerAgent;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class BrokerAgentInfolist
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
                                TextEntry::make('name_corporative')
                                    ->hiddenLabel()
                                    ->size(TextSize::Large)
                                    ->weight('semibold')
                                    ->color('primary')
                                    ->columnSpan(['md' => 6]),
                                TextEntry::make('agency_type_id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => BrokerAgentPresentation::agencyTypeLabel($state))
                                    ->color(fn (?string $state): string => BrokerAgentPresentation::agencyTypeColor($state))
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('id')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->color('gray')
                                    ->fontFamily(FontFamily::Mono)
                                    ->prefix('ID ')
                                    ->columnSpan(['md' => 3]),
                                TextEntry::make('ci_responsable')
                                    ->label('CI responsable')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->fontFamily(FontFamily::Mono)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('email')
                                    ->label('Correo')
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->iconColor('primary')
                                    ->copyable()
                                    ->url(fn (?string $state): ?string => filled($state) ? "mailto:{$state}" : null)
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                                TextEntry::make('phone')
                                    ->label('Teléfono')
                                    ->icon(Heroicon::OutlinedPhone)
                                    ->iconColor('primary')
                                    ->fontFamily(FontFamily::Mono)
                                    ->url(fn (?string $state): ?string => BrokerAgentPresentation::phoneUrl($state))
                                    ->placeholder('—')
                                    ->columnSpan(['md' => 4]),
                            ]),
                    ]),
                Tabs::make('brokerAgentTabs')
                    ->contained()
                    ->persistTabInQueryString('agente-tab')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'marketing-broker-infolist-tabs'])
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->schema([
                                Section::make('Identificación')
                                    ->description('Datos corporativos y clasificación del agente.')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('code')
                                                    ->label('Código')
                                                    ->placeholder('—')
                                                    ->badge()
                                                    ->color('gray')
                                                    ->fontFamily(FontFamily::Mono),
                                                TextEntry::make('rif')
                                                    ->label('RIF')
                                                    ->icon(Heroicon::OutlinedDocumentText)
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->placeholder('—'),
                                                TextEntry::make('birth_date')
                                                    ->label('Fecha de nacimiento')
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (?string $state): ?string => BrokerAgentPresentation::formatBirthDate($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('age')
                                                    ->label('Edad')
                                                    ->icon(Heroicon::OutlinedCalendarDays)
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->formatStateUsing(fn (mixed $state): ?string => BrokerAgentPresentation::formatAge($state))
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
                                                    ->url(fn (?string $state): ?string => filled($state) ? "mailto:{$state}" : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('phone')
                                                    ->label('Teléfono')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => BrokerAgentPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                                TextEntry::make('user_instagram')
                                                    ->label('Instagram')
                                                    ->icon(Heroicon::OutlinedCamera)
                                                    ->iconColor('primary')
                                                    ->url(fn (?string $state): ?string => BrokerAgentPresentation::instagramUrl($state))
                                                    ->placeholder('—')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('Contacto alternativo')
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->badge(fn (BrokerAgent $record): ?string => BrokerAgentPresentation::hasSecondaryContact($record->toArray()) ? '1' : null)
                            ->schema([
                                Section::make('Persona de contacto secundaria')
                                    ->description('Datos adicionales cuando existe un segundo contacto registrado.')
                                    ->icon(Heroicon::OutlinedUsers)
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('name_contact_2')
                                                    ->label('Nombre')
                                                    ->icon(Heroicon::OutlinedUser)
                                                    ->placeholder('—'),
                                                TextEntry::make('email_contact_2')
                                                    ->label('Correo')
                                                    ->icon(Heroicon::OutlinedEnvelope)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->url(fn (?string $state): ?string => filled($state) ? "mailto:{$state}" : null)
                                                    ->placeholder('—'),
                                                TextEntry::make('phone_contact_2')
                                                    ->label('Teléfono')
                                                    ->icon(Heroicon::OutlinedPhone)
                                                    ->iconColor('primary')
                                                    ->copyable()
                                                    ->fontFamily(FontFamily::Mono)
                                                    ->url(fn (?string $state): ?string => BrokerAgentPresentation::phoneUrl($state))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
