<?php

namespace App\Filament\Resources\TravelAgencies;

use App\Filament\Resources\TravelAgencies\Pages\ManageTravelAgencies;
use App\Filament\Resources\TravelAgencies\Pages\ViewTravelAgency;
use App\Filament\Resources\TravelAgencies\Schemas\TravelAgencyInfolist;
use App\Filament\Resources\TravelAgencies\Tables\TravelAgenciesTable;
use App\Models\TravelAgency;
use App\Services\Marketing\MarketingTravelAgenciesApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TravelAgencyResource extends Resource
{
    protected static ?string $model = TravelAgency::class;

    protected static ?string $navigationLabel = 'Agencias de viajes';

    protected static ?string $modelLabel = 'agencia de viajes';

    protected static ?string $pluralModelLabel = 'Agencias de viajes';

    protected static ?string $slug = 'agencias-viajes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 24;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TravelAgencyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelAgenciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTravelAgencies::route('/'),
            'view' => ViewTravelAgency::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $travelAgency = app(MarketingTravelAgenciesApiService::class)->find((string) $key);

        if ($travelAgency === null) {
            return null;
        }

        return TravelAgency::fromApi($travelAgency);
    }
}
