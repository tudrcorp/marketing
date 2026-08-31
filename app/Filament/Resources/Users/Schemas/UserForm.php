<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Cuenta')
                    ->description('Datos con los que el integrante del equipo entra al panel de marketing.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre y apellido')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name'),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->autocomplete('email'),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Section::make('Acceso')
                    ->description('El rol define qué puede ver y hacer. Sin rol, no entra al panel.')
                    ->schema([
                        Select::make('marketing_role_id')
                            ->label('Rol de marketing')
                            ->relationship('marketingRole', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->dehydrated()
                            ->disabled(fn (?User $record): bool => $record?->isLastMarketingAdministrator() ?? false)
                            ->helperText(fn (?User $record): ?string => $record?->isLastMarketingAdministrator()
                                ? 'No puedes quitar el rol al último administrador del panel.'
                                : 'Asigna un perfil: administrador, analista, aprobador o visor.')
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->confirmed()
                            ->live(onBlur: true)
                            ->autocomplete('new-password')
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Déjala en blanco para conservar la contraseña actual.'
                                : 'El usuario la usará en el acceso al panel.'),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(false)
                            ->visible(fn (Get $get, string $operation): bool => $operation === 'create' || filled($get('password')))
                            ->autocomplete('new-password'),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ]),
            ]);
    }
}
