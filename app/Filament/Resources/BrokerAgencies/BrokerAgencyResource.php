<?php

namespace App\Filament\Resources\BrokerAgencies;

use App\Filament\Resources\BrokerAgencies\Pages\ManageBrokerAgencies;
use App\Filament\Resources\BrokerAgencies\Pages\ViewBrokerAgency;
use App\Filament\Resources\BrokerAgencies\Schemas\BrokerAgencyInfolist;
use App\Filament\Resources\BrokerAgencies\Tables\BrokerAgenciesTable;
use App\Models\BrokerAgency;
use App\Services\Marketing\MarketingAgenciesApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BrokerAgencyResource extends Resource
{
    protected static ?string $model = BrokerAgency::class;

    protected static ?string $navigationLabel = 'Agencias de corretaje';

    protected static ?string $modelLabel = 'agencia de corretaje';

    protected static ?string $pluralModelLabel = 'Agencias de corretaje';

    protected static ?string $slug = 'agencias-corretaje';

    protected static ?string $recordTitleAttribute = 'name_corporative';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return BrokerAgencyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrokerAgenciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrokerAgencies::route('/'),
            'view' => ViewBrokerAgency::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $agency = app(MarketingAgenciesApiService::class)->find((string) $key);

        if ($agency === null) {
            return null;
        }

        return BrokerAgency::fromApi($agency);
    }
}
