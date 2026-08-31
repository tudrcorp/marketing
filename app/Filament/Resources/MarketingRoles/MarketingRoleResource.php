<?php

namespace App\Filament\Resources\MarketingRoles;

use App\Filament\Resources\MarketingRoles\Pages\CreateMarketingRole;
use App\Filament\Resources\MarketingRoles\Pages\EditMarketingRole;
use App\Filament\Resources\MarketingRoles\Pages\ListMarketingRoles;
use App\Filament\Resources\MarketingRoles\Schemas\MarketingRoleForm;
use App\Filament\Resources\MarketingRoles\Tables\MarketingRolesTable;
use App\Models\MarketingRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MarketingRoleResource extends Resource
{
    protected static ?string $model = MarketingRole::class;

    protected static ?string $navigationLabel = 'Roles';

    protected static ?string $modelLabel = 'rol de marketing';

    protected static ?string $pluralModelLabel = 'Roles de marketing';

    protected static ?string $slug = 'roles-marketing';

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MarketingRoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketingRolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketingRoles::route('/'),
            'create' => CreateMarketingRole::route('/create'),
            'edit' => EditMarketingRole::route('/{record}/edit'),
        ];
    }
}
