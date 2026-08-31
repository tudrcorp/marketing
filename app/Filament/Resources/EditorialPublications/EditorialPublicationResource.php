<?php

namespace App\Filament\Resources\EditorialPublications;

use App\Filament\Resources\EditorialPublications\Pages\CreateEditorialPublication;
use App\Filament\Resources\EditorialPublications\Pages\EditEditorialPublication;
use App\Filament\Resources\EditorialPublications\Pages\ListEditorialPublications;
use App\Filament\Resources\EditorialPublications\Schemas\EditorialPublicationForm;
use App\Filament\Resources\EditorialPublications\Tables\EditorialPublicationsTable;
use App\Models\EditorialPublication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EditorialPublicationResource extends Resource
{
    protected static ?string $model = EditorialPublication::class;

    protected static ?string $navigationLabel = 'Publicaciones';

    protected static ?string $modelLabel = 'publicación';

    protected static ?string $pluralModelLabel = 'Publicaciones editoriales';

    protected static ?string $slug = 'publicaciones';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 12;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return EditorialPublicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EditorialPublicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEditorialPublications::route('/'),
            'create' => CreateEditorialPublication::route('/create'),
            'edit' => EditEditorialPublication::route('/{record}/edit'),
        ];
    }
}
