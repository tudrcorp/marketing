<?php

namespace App\Filament\Resources\MassNotifications\Schemas;

use App\Filament\Support\AudienceCheckboxList;
use App\Filament\Support\ChannelToggleButtons;
use App\Marketing\MassNotificationContentType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MassNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Contenido de la notificación')
                    ->description('El encabezado y el copy son obligatorios. El resto del contenido es opcional según la campaña.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Encabezado')
                            ->required()
                            ->maxLength(180)
                            ->placeholder('Ej. Promoción especial TDG')
                            ->columnSpanFull(),
                        Textarea::make('copy')
                            ->label('Copy del mensaje')
                            ->helperText('Texto principal que recibirán los destinatarios por WhatsApp, correo o SMS.')
                            ->required()
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
                Section::make('Canales de envío')
                    ->description('Selecciona uno o más canales para el envío masivo.')
                    ->schema([
                        ChannelToggleButtons::make('channels', hiddenLabel: true),
                    ]),
                Section::make('Grupos destinatarios')
                    ->description('Selecciona los grupos TDG que recibirán esta notificación.')
                    ->schema([
                        AudienceCheckboxList::make(hiddenLabel: true),
                    ]),
                Section::make('Contenido adicional')
                    ->description('Opcional. Elige el tipo de adjunto solo si la campaña lo requiere.')
                    ->schema([
                        Select::make('content_type')
                            ->label('Tipo de envío')
                            ->options(MassNotificationContentType::class)
                            ->placeholder('Sin adjunto')
                            ->native(false)
                            ->live()
                            ->nullable()
                            ->dehydrateStateUsing(fn (mixed $state): ?string => MassNotificationContentType::tryFromMixed($state)?->value)
                            ->columnSpanFull(),
                        FileUpload::make('attachment')
                            ->label('Archivo adjunto')
                            ->helperText(fn (Get $get): string => match (MassNotificationContentType::tryFromMixed($get('content_type'))) {
                                MassNotificationContentType::Image => 'Sube una imagen para acompañar el mensaje.',
                                MassNotificationContentType::Video => 'Sube un video para acompañar el mensaje.',
                                MassNotificationContentType::File => 'Sube un archivo de soporte para la campaña.',
                                default => 'Selecciona primero el tipo de envío.',
                            })
                            ->disk('public')
                            ->directory('mass-notifications/attachments')
                            ->visibility('public')
                            ->maxSize(51_200)
                            ->acceptedFileTypes(fn (Get $get): array => MassNotificationContentType::tryFromMixed($get('content_type'))?->acceptedMimeTypes() ?? [])
                            ->visible(fn (Get $get): bool => filled($get('content_type')))
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
