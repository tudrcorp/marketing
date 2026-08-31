<?php

namespace App\Filament\Resources\CorporateEvents;

use App\Filament\Resources\CorporateEvents\Pages\CreateCorporateEvent;
use App\Filament\Resources\CorporateEvents\Pages\EditCorporateEvent;
use App\Filament\Resources\CorporateEvents\Pages\ListCorporateEvents;
use App\Filament\Resources\CorporateEvents\Pages\ViewCorporateEvent;
use App\Filament\Resources\CorporateEvents\RelationManagers\RegistrationsRelationManager;
use App\Filament\Resources\CorporateEvents\Schemas\CorporateEventForm;
use App\Filament\Resources\CorporateEvents\Schemas\CorporateEventInfolist;
use App\Filament\Resources\CorporateEvents\Tables\CorporateEventsTable;
use App\Models\CorporateEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CorporateEventResource extends Resource
{
    protected static ?string $model = CorporateEvent::class;

    protected static ?string $navigationLabel = 'Eventos corporativos';

    protected static ?string $modelLabel = 'evento corporativo';

    protected static ?string $pluralModelLabel = 'Eventos corporativos';

    protected static ?string $slug = 'eventos-corporativos';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 13;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return CorporateEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CorporateEventsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CorporateEventInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            RegistrationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCorporateEvents::route('/'),
            'create' => CreateCorporateEvent::route('/create'),
            'view' => ViewCorporateEvent::route('/{record}'),
            'edit' => EditCorporateEvent::route('/{record}/edit'),
        ];
    }
}
