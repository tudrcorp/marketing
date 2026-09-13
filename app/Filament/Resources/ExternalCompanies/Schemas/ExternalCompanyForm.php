<?php

namespace App\Filament\Resources\ExternalCompanies\Schemas;

use App\Marketing\ExternalCompanyType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ExternalCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(fn (Get $get): string => self::type($get)->getLabel())
                    ->description('Datos del externo que Marketing administra para campañas.')
                    ->schema([
                        ToggleButtons::make('type')
                            ->label('Tipo de externo')
                            ->options(ExternalCompanyType::class)
                            ->default(ExternalCompanyType::Company)
                            ->required()
                            ->inline()
                            ->live()
                            ->helperText('Una persona natural no declara razón social y sus datos de responsable son opcionales.')
                            ->columnSpanFull(),
                        TextInput::make('company_name')
                            ->label(fn (Get $get): string => self::type($get)->nameLabel())
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        TextInput::make('legal_name')
                            ->label('Razón social')
                            ->required()
                            ->maxLength(180)
                            ->visible(fn (Get $get): bool => self::type($get)->isCompany())
                            ->columnSpanFull(),
                        TextInput::make('document_id')
                            ->label(fn (Get $get): string => self::type($get)->documentLabel())
                            ->required()
                            ->maxLength(20)
                            ->helperText(fn (Get $get): string => self::type($get)->isCompany()
                                ? 'RIF jurídico (J-).'
                                : 'Cédula de identidad (V-/E-).'),
                        TextInput::make('phone')
                            ->label('Número de teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(30)
                            ->helperText(fn (Get $get): string => self::type($get)->isCompany()
                                ? 'Este número se usará en WhatsApp y SMS cuando se notifique a la empresa.'
                                : 'Este número se usará en WhatsApp y SMS cuando se notifique a esta persona.'),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(180),
                    ])
                    ->columns(2),
                Section::make('Responsable')
                    ->description(fn (Get $get): string => self::type($get)->isCompany()
                        ? 'Las respuestas de WhatsApp o SMS se enrutan a este contacto.'
                        : 'Contacto alterno opcional. Si se deja vacío, las respuestas se enrutan al teléfono de la persona.')
                    ->schema([
                        TextInput::make('responsible_name')
                            ->label('Nombre y apellido')
                            ->required(fn (Get $get): bool => self::type($get)->isCompany())
                            ->maxLength(180)
                            ->columnSpanFull(),
                        TextInput::make('responsible_document_id')
                            ->label('RIF / CI del responsable')
                            ->required(fn (Get $get): bool => self::type($get)->isCompany())
                            ->maxLength(20),
                        TextInput::make('responsible_phone')
                            ->label('Teléfono del responsable')
                            ->tel()
                            ->required(fn (Get $get): bool => self::type($get)->isCompany())
                            ->maxLength(30)
                            ->helperText('Este número se usará como contacto de respuesta en WhatsApp y SMS.'),
                        TextInput::make('responsible_email')
                            ->label('Correo del responsable')
                            ->email()
                            ->required(fn (Get $get): bool => self::type($get)->isCompany())
                            ->maxLength(180),
                    ])
                    ->columns(2),
            ]);
    }

    private static function type(Get $get): ExternalCompanyType
    {
        $type = $get('type');

        if ($type instanceof ExternalCompanyType) {
            return $type;
        }

        return ExternalCompanyType::tryFrom((string) $type) ?? ExternalCompanyType::Company;
    }
}
