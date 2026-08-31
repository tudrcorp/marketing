<?php

namespace App\Filament\Resources\BirthdayNotifications\Schemas;

use App\Filament\Support\AudienceCheckboxList;
use App\Filament\Support\ChannelToggleButtons;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BirthdayNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Notificación de cumpleaños')
                    ->description('Configura el mensaje y las audiencias que recibirán la felicitación. El envío masivo se implementará en una fase posterior.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre interno')
                            ->required()
                            ->maxLength(180)
                            ->placeholder('Ej. Felicitación cumpleaños marzo 2026')
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Imagen')
                            ->helperText('Imagen que acompañará el mensaje en los canales seleccionados.')
                            ->image()
                            ->disk('public')
                            ->directory('birthday-notifications/images')
                            ->visibility('public')
                            ->maxSize(5_120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->nullable()
                            ->columnSpanFull(),
                        Textarea::make('copy')
                            ->label('Texto del mensaje')
                            ->helperText('Copy que recibirá el cliente por WhatsApp, correo electrónico o SMS.')
                            ->required()
                            ->rows(6)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        ChannelToggleButtons::make('channels', label: 'Canales de envío'),
                        AudienceCheckboxList::make(),
                    ]),
            ]);
    }
}
