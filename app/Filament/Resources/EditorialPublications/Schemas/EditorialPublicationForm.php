<?php

namespace App\Filament\Resources\EditorialPublications\Schemas;

use App\Marketing\PublicationStatus;
use App\Models\SocialAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditorialPublicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Publicación editorial')
                    ->description('Programa contenido por red social en el calendario editorial TDG.')
                    ->schema([
                        Grid::make(2)->schema([
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
                                ->label('Fecha y hora programada')
                                ->required()
                                ->seconds(false)
                                ->native(false)
                                ->minDate(now()->startOfDay()),
                            Select::make('status')
                                ->label('Estado')
                                ->options(PublicationStatus::class)
                                ->required()
                                ->default(PublicationStatus::Draft)
                                ->native(false),
                        ]),
                        TextInput::make('title')
                            ->label('Título interno')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Contenido de la publicación')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                        EditorialPublicationCalendarForm::referenceImageField(),
                        TagsInput::make('hashtags')
                            ->label('Hashtags')
                            ->placeholder('Agregar hashtag')
                            ->columnSpanFull(),
                        TagsInput::make('media_urls')
                            ->label('URLs de medios')
                            ->placeholder('https://...')
                            ->columnSpanFull(),
                        Textarea::make('approval_notes')
                            ->label('Notas de aprobación')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (): bool => auth()->user()?->hasMarketingPermission(\App\Marketing\MarketingPermission::ApprovePublications) ?? false),
                    ]),
            ]);
    }
}
