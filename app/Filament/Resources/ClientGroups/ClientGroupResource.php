<?php

namespace App\Filament\Resources\ClientGroups;

use App\Filament\Resources\ClientGroups\Pages\CreateClientGroup;
use App\Filament\Resources\ClientGroups\Pages\EditClientGroup;
use App\Filament\Resources\ClientGroups\Pages\ListClientGroups;
use App\Filament\Resources\ClientGroups\Pages\ViewClientGroup;
use App\Filament\Resources\ClientGroups\RelationManagers\ClientsRelationManager;
use App\Filament\Resources\ClientGroups\Schemas\ClientGroupForm;
use App\Filament\Resources\ClientGroups\Schemas\ClientGroupInfolist;
use App\Filament\Resources\ClientGroups\Tables\ClientGroupsTable;
use App\Models\ClientGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClientGroupResource extends Resource
{
    protected static ?string $model = ClientGroup::class;

    protected static ?string $navigationLabel = 'Grupos de clientes';

    protected static ?string $modelLabel = 'grupo de clientes';

    protected static ?string $pluralModelLabel = 'Grupos de clientes';

    protected static ?string $slug = 'grupos-clientes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 17;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return ClientGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientGroupsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientGroupInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ClientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientGroups::route('/'),
            'create' => CreateClientGroup::route('/create'),
            'view' => ViewClientGroup::route('/{record}'),
            'edit' => EditClientGroup::route('/{record}/edit'),
        ];
    }
}
