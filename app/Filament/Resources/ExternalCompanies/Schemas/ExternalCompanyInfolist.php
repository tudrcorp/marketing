<?php

namespace App\Filament\Resources\ExternalCompanies\Schemas;

use App\Models\ExternalCompany;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class ExternalCompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(fn (ExternalCompany $record): string => $record->type->getLabel())
                    ->schema([
                        TextEntry::make('type')
                            ->label('Tipo de externo')
                            ->badge(),
                        TextEntry::make('company_name')
                            ->label(fn (ExternalCompany $record): string => $record->type->nameLabel())
                            ->weight(FontWeight::SemiBold)
                            ->icon(fn (ExternalCompany $record): Heroicon => $record->type->getIcon()),
                        TextEntry::make('legal_name')
                            ->label('Razón social')
                            ->icon(Heroicon::OutlinedBuildingOffice)
                            ->visible(fn (ExternalCompany $record): bool => filled($record->legal_name)),
                        TextEntry::make('document_id')
                            ->label(fn (ExternalCompany $record): string => $record->type->documentLabel())
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->icon(Heroicon::OutlinedIdentification),
                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->copyable()
                            ->icon(Heroicon::OutlinedPhone),
                        TextEntry::make('email')
                            ->label('Correo')
                            ->copyable()
                            ->icon(Heroicon::OutlinedEnvelope),
                    ])
                    ->columns(3),
                Section::make('Responsable')
                    ->description(fn (ExternalCompany $record): string => $record->isNaturalPerson()
                        ? 'Contacto alterno de esta persona natural. Es opcional.'
                        : 'Contacto al que se enrutan las respuestas de WhatsApp y SMS de esta empresa.')
                    ->schema([
                        TextEntry::make('responsible_name')
                            ->label('Nombre y apellido')
                            ->placeholder('—')
                            ->icon(Heroicon::OutlinedUser),
                        TextEntry::make('responsible_document_id')
                            ->label('RIF / CI')
                            ->placeholder('—')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->icon(Heroicon::OutlinedIdentification),
                        TextEntry::make('responsible_phone')
                            ->label('Teléfono')
                            ->placeholder('—')
                            ->copyable()
                            ->icon(Heroicon::OutlinedPhone),
                        TextEntry::make('responsible_email')
                            ->label('Correo')
                            ->placeholder('—')
                            ->copyable()
                            ->icon(Heroicon::OutlinedEnvelope),
                    ])
                    ->columns(2),
            ]);
    }
}
