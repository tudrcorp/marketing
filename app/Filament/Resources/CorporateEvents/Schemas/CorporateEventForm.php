<?php

namespace App\Filament\Resources\CorporateEvents\Schemas;

use App\Filament\Support\AudienceCheckboxList;
use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CorporateEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Información general')
                    ->description('Define el tipo de actividad, título y descripción del evento.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        Select::make('event_type')
                            ->label('Tipo de evento')
                            ->options(CorporateEventType::class)
                            ->required()
                            ->native(false),
                        Select::make('modality')
                            ->label('Modalidad')
                            ->options(CorporateEventModality::class)
                            ->required()
                            ->native(false),
                        Textarea::make('summary')
                            ->label('Resumen')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Descripción detallada')
                            ->rows(5)
                            ->maxLength(10000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Fechas y ubicación')
                    ->description('Programa el evento y define dónde se realizará.')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Inicio')
                            ->required()
                            ->seconds(false)
                            ->native(false),
                        DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->seconds(false)
                            ->native(false)
                            ->after('starts_at'),
                        TextInput::make('venue_name')
                            ->label('Sede / lugar')
                            ->maxLength(180),
                        TextInput::make('venue_address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('virtual_url')
                            ->label('Enlace virtual')
                            ->url()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Audiencias y cupos')
                    ->description('Selecciona los grupos TDG a los que va dirigido y configura inscripciones.')
                    ->schema([
                        AudienceCheckboxList::make(
                            name: 'target_audiences',
                            label: 'Grupos destinatarios',
                        ),
                        TextInput::make('capacity')
                            ->label('Cupo máximo')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Sin límite'),
                        DateTimePicker::make('registration_deadline')
                            ->label('Cierre de inscripciones')
                            ->helperText('La URL pública de inscripción caducará en esta fecha y hora.')
                            ->seconds(false)
                            ->native(false),
                        Placeholder::make('registration_url_notice')
                            ->label('URL de inscripción pública')
                            ->content('Se generará automáticamente al guardar el evento.')
                            ->visibleOn('create')
                            ->columnSpanFull(),
                        TextInput::make('registration_url')
                            ->label('URL de inscripción pública')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->helperText(fn (Get $get): string => filled($get('registration_deadline'))
                                ? 'Comparte este enlace con los invitados. Caduca al cierre de inscripciones.'
                                : 'Comparte este enlace con los invitados. Sin fecha de cierre, permanece activo hasta completar cupo o cancelar el evento.')
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Medios')
                    ->description('Imagen de portada y archivos de apoyo opcionales.')
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Imagen de portada')
                            ->disk('public')
                            ->directory('corporate-events/covers')
                            ->visibility('public')
                            ->image()
                            ->maxSize(10_240)
                            ->columnSpanFull(),
                        FileUpload::make('attachments')
                            ->label('Archivos adjuntos')
                            ->disk('public')
                            ->directory('corporate-events/attachments')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(51_200)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
