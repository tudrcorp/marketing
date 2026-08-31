<?php

namespace App\Filament\Resources\CorporateAffiliates;

use App\Filament\Resources\CorporateAffiliates\Pages\ManageCorporateAffiliates;
use App\Filament\Resources\CorporateAffiliates\Pages\ViewCorporateAffiliate;
use App\Filament\Resources\CorporateAffiliates\Schemas\CorporateAffiliateInfolist;
use App\Filament\Resources\CorporateAffiliates\Tables\CorporateAffiliatesTable;
use App\Models\CorporateAffiliate;
use App\Services\Marketing\MarketingCorporateAffiliatesApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CorporateAffiliateResource extends Resource
{
    protected static ?string $model = CorporateAffiliate::class;

    protected static ?string $navigationLabel = 'Afiliados corporativos';

    protected static ?string $modelLabel = 'afiliado corporativo';

    protected static ?string $pluralModelLabel = 'Afiliados corporativos';

    protected static ?string $slug = 'afiliados-corporativos';

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 23;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CorporateAffiliateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CorporateAffiliatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCorporateAffiliates::route('/'),
            'view' => ViewCorporateAffiliate::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $affiliate = app(MarketingCorporateAffiliatesApiService::class)->find((string) $key);

        if ($affiliate === null) {
            return null;
        }

        return CorporateAffiliate::fromApi($affiliate);
    }
}
