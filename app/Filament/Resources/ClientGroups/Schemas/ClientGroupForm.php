<?php

namespace App\Filament\Resources\ClientGroups\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Grupo')
                    ->description('Identifica el grupo y asígnale un color para reconocerlo en el panel.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del grupo')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),
                        ColorPicker::make('color')
                            ->label('Color')
                            ->default('#3b82f6')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Responsable del grupo')
                    ->description('Las notificaciones por WhatsApp o SMS a clientes de este grupo usarán el teléfono del responsable para que las respuestas lleguen a la persona correcta.')
                    ->schema([
                        TextInput::make('responsible_name')
                            ->label('Nombre y apellido')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        TextInput::make('responsible_email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->maxLength(180),
                        TextInput::make('responsible_phone')
                            ->label('Teléfono de contacto')
                            ->tel()
                            ->required()
                            ->maxLength(30)
                            ->helperText('Este número se usará en WhatsApp y SMS cuando se notifique a los clientes del grupo.'),
                    ])
                    ->columns(2),
            ]);
    }
}
