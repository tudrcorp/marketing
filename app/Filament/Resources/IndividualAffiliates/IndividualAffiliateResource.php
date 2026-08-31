<?php

namespace App\Filament\Resources\IndividualAffiliates;

use App\Filament\Resources\IndividualAffiliates\Pages\ManageIndividualAffiliates;
use App\Filament\Resources\IndividualAffiliates\Pages\ViewIndividualAffiliate;
use App\Filament\Resources\IndividualAffiliates\Schemas\IndividualAffiliateInfolist;
use App\Filament\Resources\IndividualAffiliates\Tables\IndividualAffiliatesTable;
use App\Models\IndividualAffiliate;
use App\Services\Marketing\MarketingIndividualAffiliatesApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class IndividualAffiliateResource extends Resource
{
    protected static ?string $model = IndividualAffiliate::class;

    protected static ?string $navigationLabel = 'Afiliados individuales';

    protected static ?string $modelLabel = 'afiliado individual';

    protected static ?string $pluralModelLabel = 'Afiliados individuales';

    protected static ?string $slug = 'afiliados-individuales';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 22;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return IndividualAffiliateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IndividualAffiliatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIndividualAffiliates::route('/'),
            'view' => ViewIndividualAffiliate::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $affiliate = app(MarketingIndividualAffiliatesApiService::class)->find((string) $key);

        if ($affiliate === null) {
            return null;
        }

        return IndividualAffiliate::fromApi($affiliate);
    }
}
