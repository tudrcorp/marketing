<?php

namespace App\Filament\Support;

use App\Marketing\ContentPillar;
use App\Marketing\ContentPostBlocker;
use App\Marketing\ContentPostFormat;
use App\Marketing\ContentPostPriority;
use App\Marketing\ContentPostStatus;
use App\Models\Brand;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

/**
 * Formulario compartido por el tablero Kanban y el calendario de contenido.
 */
class ContentPostFormSchema
{
    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Section::make('Pieza de contenido')
                ->schema([
                    Select::make('brand_id')
                        ->label('Marca')
                        ->options(fn (): array => Brand::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(180)
                        ->columnSpanFull(),
                    Textarea::make('copy')
                        ->label('Copy')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make('Producción')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options(ContentPostStatus::class)
                            ->default(ContentPostStatus::Idea)
                            ->required(),
                        Select::make('priority')
                            ->label('Prioridad')
                            ->options(ContentPostPriority::class)
                            ->default(ContentPostPriority::Medium)
                            ->required(),
                        Select::make('format')
                            ->label('Formato')
                            ->options(ContentPostFormat::class)
                            ->default(ContentPostFormat::Static)
                            ->required(),
                        Select::make('pillar')
                            ->label('Pilar de contenido')
                            ->options(ContentPillar::class)
                            ->placeholder('Sin pilar asignado'),
                    ]),
                    ToggleButtons::make('blocker')
                        ->label('Bloqueo')
                        ->options(ContentPostBlocker::class)
                        ->default(ContentPostBlocker::None)
                        ->inline()
                        ->required()
                        ->columnSpanFull(),
                    DateTimePicker::make('scheduled_at')
                        ->label('Fecha y hora de publicación')
                        ->seconds(false)
                        ->native(false)
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make('Recursos y reutilización')
                ->schema([
                    FileUpload::make('reference_image')
                        ->label('Imagen de referencia')
                        ->image()
                        ->imageEditor()
                        ->directory('content-posts/references')
                        ->maxSize(5120)
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        Toggle::make('is_replicable')
                            ->label('Replicable en otras marcas')
                            ->helperText('Se agrupa en el módulo de plantillas replicables.'),
                        Toggle::make('is_evergreen')
                            ->label('Contenido comodín (evergreen)')
                            ->helperText('Queda disponible en el banco comodín para emergencias.'),
                    ]),
                ])
                ->columns(1)
                ->collapsed(),
        ];
    }
}
