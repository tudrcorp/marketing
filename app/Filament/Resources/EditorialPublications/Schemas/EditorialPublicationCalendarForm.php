<?php

namespace App\Filament\Resources\EditorialPublications\Schemas;

use App\Marketing\PublicationStatus;
use App\Models\SocialAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class EditorialPublicationCalendarForm
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function createComponents(): array
    {
        return [
            Select::make('social_account_id')
                ->label('Cuenta de red social')
                ->options(fn () => SocialAccount::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->native(false),
            DateTimePicker::make('scheduled_at')
                ->label('Fecha y hora')
                ->required()
                ->seconds(false)
                ->native(false)
                ->minDate(now()->startOfDay()),
            TextInput::make('title')
                ->label('Título interno')
                ->required()
                ->maxLength(180)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label('Contenido')
                ->required()
                ->rows(5)
                ->columnSpanFull(),
            self::referenceImageField(),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function components(): array
    {
        return [
            Select::make('social_account_id')
                ->label('Cuenta de red social')
                ->options(fn () => SocialAccount::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->native(false),
            DateTimePicker::make('scheduled_at')
                ->label('Fecha y hora')
                ->required()
                ->seconds(false)
                ->native(false),
            TextInput::make('title')
                ->label('Título interno')
                ->required()
                ->maxLength(180)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label('Contenido')
                ->required()
                ->rows(5)
                ->columnSpanFull(),
            self::referenceImageField(),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function editComponents(): array
    {
        return [
            ...self::components(),
            Select::make('status')
                ->label('Estado')
                ->options(PublicationStatus::class)
                ->required()
                ->native(false)
                ->columnSpanFull(),
        ];
    }

    public static function referenceImageField(): FileUpload
    {
        return FileUpload::make('reference_image')
            ->label('Imagen de referencia')
            ->helperText('Sube un mockup o captura visual de referencia para la publicación.')
            ->image()
            ->disk('public')
            ->directory('editorial-publications/reference-images')
            ->visibility('public')
            ->maxSize(5_120)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->nullable()
            ->columnSpanFull();
    }
}
