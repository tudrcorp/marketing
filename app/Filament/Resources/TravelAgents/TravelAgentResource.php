<?php

namespace App\Filament\Resources\TravelAgents;

use App\Filament\Resources\TravelAgents\Pages\ManageTravelAgents;
use App\Filament\Resources\TravelAgents\Pages\ViewTravelAgent;
use App\Filament\Resources\TravelAgents\Schemas\TravelAgentInfolist;
use App\Filament\Resources\TravelAgents\Tables\TravelAgentsTable;
use App\Models\TravelAgent;
use App\Services\Marketing\MarketingTravelAgentsApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TravelAgentResource extends Resource
{
    protected static ?string $model = TravelAgent::class;

    protected static ?string $navigationLabel = 'Agentes de viajes';

    protected static ?string $modelLabel = 'agente de viajes';

    protected static ?string $pluralModelLabel = 'Agentes de viajes';

    protected static ?string $slug = 'agentes-viajes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TravelAgentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelAgentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTravelAgents::route('/'),
            'view' => ViewTravelAgent::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $travelAgent = app(MarketingTravelAgentsApiService::class)->find((string) $key);

        if ($travelAgent === null) {
            return null;
        }

        return TravelAgent::fromApi($travelAgent);
    }
}
