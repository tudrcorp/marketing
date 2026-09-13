<?php

namespace App\Filament\Resources\ExternalCompanies;

use App\Filament\Resources\ExternalCompanies\Pages\CreateExternalCompany;
use App\Filament\Resources\ExternalCompanies\Pages\EditExternalCompany;
use App\Filament\Resources\ExternalCompanies\Pages\ListExternalCompanies;
use App\Filament\Resources\ExternalCompanies\Pages\ViewExternalCompany;
use App\Filament\Resources\ExternalCompanies\Schemas\ExternalCompanyForm;
use App\Filament\Resources\ExternalCompanies\Schemas\ExternalCompanyInfolist;
use App\Filament\Resources\ExternalCompanies\Tables\ExternalCompaniesTable;
use App\Models\ExternalCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExternalCompanyResource extends Resource
{
    protected static ?string $model = ExternalCompany::class;

    protected static ?string $navigationLabel = 'Externos';

    protected static ?string $modelLabel = 'externo';

    protected static ?string $pluralModelLabel = 'Externos';

    protected static ?string $slug = 'externos';

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 18;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return ExternalCompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExternalCompaniesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExternalCompanyInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExternalCompanies::route('/'),
            'create' => CreateExternalCompany::route('/create'),
            'view' => ViewExternalCompany::route('/{record}'),
            'edit' => EditExternalCompany::route('/{record}/edit'),
        ];
    }
}
