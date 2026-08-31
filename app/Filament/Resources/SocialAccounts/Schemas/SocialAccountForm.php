<?php

namespace App\Filament\Resources\SocialAccounts\Schemas;

use App\Marketing\SocialPlatform;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SocialAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Cuenta de red social')
                    ->description('Registra las cuentas oficiales de TDG para programar y validar publicaciones.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nombre interno')
                                ->required()
                                ->maxLength(120)
                                ->placeholder('TDG Instagram Venezuela'),
                            Select::make('platform')
                                ->label('Red social')
                                ->options(SocialPlatform::selectOptions())
                                ->getOptionLabelUsing(fn (?string $value): ?string => SocialPlatform::selectLabelFor($value))
                                ->allowHtml()
                                ->required()
                                ->native(false)
                                ->searchable(),
                            TextInput::make('handle')
                                ->label('Usuario / handle')
                                ->maxLength(120)
                                ->placeholder('@tudoctorgroup')
                                ->disabled(fn (): bool => ! self::canManageCredentials())
                                ->dehydrated(fn (): bool => self::canManageCredentials())
                                ->helperText(fn (): ?string => self::canManageCredentials()
                                    ? null
                                    : 'Solo un administrador puede modificar el usuario de la red social.'),
                            TextInput::make('account_password')
                                ->label('Clave de acceso')
                                ->password()
                                ->revealable()
                                ->maxLength(255)
                                ->visible(fn (): bool => self::canManageCredentials())
                                ->dehydrated(fn (?string $state): bool => self::canManageCredentials() && filled($state))
                                ->placeholder('Ingresa la clave de la cuenta')
                                ->helperText('Se guarda cifrada automáticamente. En edición, déjala en blanco para conservar la clave actual.'),
                            Placeholder::make('credential_admin_only')
                                ->label('Credenciales de acceso')
                                ->visible(fn (): bool => ! self::canManageCredentials())
                                ->content('Solo un administrador de marketing puede registrar o actualizar el usuario y la clave de esta cuenta.')
                                ->columnSpanFull(),
                            TextInput::make('profile_url')
                                ->label('URL del perfil')
                                ->url()
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),
                        Toggle::make('is_active')
                            ->label('Cuenta activa')
                            ->default(true)
                            ->inline(false),
                        Textarea::make('notes')
                            ->label('Notas operativas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Seguridad de credenciales')
                    ->description('El usuario y la clave solo pueden ser editados por un administrador. La clave se almacena cifrada y puede consultarse con la acción "Ver clave".')
                    ->collapsed()
                    ->visible(fn (): bool => self::canManageCredentials())
                    ->schema([
                        Placeholder::make('credential_security_hint')
                            ->hiddenLabel()
                            ->content('1. El administrador ingresa el usuario y la clave, luego guarda la cuenta. 2. El sistema cifra la clave automáticamente. 3. Puedes abrir "Ver clave" desde la tabla o la edición para consultarla en texto claro.'),
                    ]),
            ]);
    }

    protected static function canManageCredentials(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isMarketingAdministrator();
    }
}
