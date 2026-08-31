<?php

namespace App\Filament\Resources\SocialAccounts;

use App\Filament\Resources\SocialAccounts\Pages\CreateSocialAccount;
use App\Filament\Resources\SocialAccounts\Pages\EditSocialAccount;
use App\Filament\Resources\SocialAccounts\Pages\ListSocialAccounts;
use App\Filament\Resources\SocialAccounts\Schemas\SocialAccountForm;
use App\Filament\Resources\SocialAccounts\Tables\SocialAccountsTable;
use App\Models\SocialAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static ?string $navigationLabel = 'Cuentas de redes';

    protected static ?string $modelLabel = 'cuenta de red social';

    protected static ?string $pluralModelLabel = 'Cuentas de redes sociales';

    protected static ?string $slug = 'cuentas-redes';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SocialAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SocialAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialAccounts::route('/'),
            'create' => CreateSocialAccount::route('/create'),
            'edit' => EditSocialAccount::route('/{record}/edit'),
        ];
    }
}
