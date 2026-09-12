<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidad de marca')
                    ->description('Ficha técnica visible en el panel de contenido multimarca.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, callable $set) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label('Identificador')
                            ->required()
                            ->maxLength(140)
                            ->unique(ignoreRecord: true)
                            ->helperText('Se usa en enlaces y reportes. Solo minúsculas y guiones.'),
                        ColorPicker::make('color_hex')
                            ->label('Color de marca')
                            ->required()
                            ->default('#f9b17a'),
                        Toggle::make('is_active')
                            ->label('Marca activa')
                            ->default(true),
                        Textarea::make('brand_voice')
                            ->label('Voz de marca')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Carpetas en la nube')
                    ->schema([
                        TextInput::make('drive_url')
                            ->label('Carpeta de Drive')
                            ->url()
                            ->maxLength(500),
                        TextInput::make('canva_url')
                            ->label('Espacio de Canva')
                            ->url()
                            ->maxLength(500),
                    ])
                    ->columns(2),

                Section::make('Bóveda de recursos fijos')
                    ->description('CTAs, bloques de hashtags y enlaces frecuentes listos para copiar.')
                    ->schema([
                        self::vaultRepeater('ctas', 'CTAs', 'CTA', 'Texto del llamado a la acción'),
                        self::vaultRepeater('hashtag_groups', 'Grupos de hashtags', 'Grupo', 'Bloque de hashtags'),
                        self::vaultRepeater('quick_links', 'Enlaces frecuentes', 'Enlace', 'URL o texto'),
                    ])
                    ->columns(1)
                    ->collapsed(),
            ]);
    }

    protected static function vaultRepeater(string $name, string $label, string $itemLabel, string $valuePlaceholder): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema([
                TextInput::make('label')
                    ->label('Etiqueta')
                    ->required()
                    ->maxLength(80),
                TextInput::make('value')
                    ->label('Contenido')
                    ->placeholder($valuePlaceholder)
                    ->required()
                    ->maxLength(1000),
            ])
            ->columns(2)
            ->addActionLabel('Añadir '.Str::lower($itemLabel))
            ->reorderable()
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
            ->default([]);
    }
}
