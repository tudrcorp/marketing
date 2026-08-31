<?php

namespace App\Filament\Resources\BrokerAgents;

use App\Filament\Resources\BrokerAgents\Pages\ManageBrokerAgents;
use App\Filament\Resources\BrokerAgents\Pages\ViewBrokerAgent;
use App\Filament\Resources\BrokerAgents\Schemas\BrokerAgentInfolist;
use App\Filament\Resources\BrokerAgents\Tables\BrokerAgentsTable;
use App\Models\BrokerAgent;
use App\Services\Marketing\MarketingAgentsApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BrokerAgentResource extends Resource
{
    protected static ?string $model = BrokerAgent::class;

    protected static ?string $navigationLabel = 'Agentes de corretaje';

    protected static ?string $modelLabel = 'agente de corretaje';

    protected static ?string $pluralModelLabel = 'Agentes de corretaje';

    protected static ?string $slug = 'agentes-corretaje';

    protected static ?string $recordTitleAttribute = 'name_corporative';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return BrokerAgentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrokerAgentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrokerAgents::route('/'),
            'view' => ViewBrokerAgent::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $agent = app(MarketingAgentsApiService::class)->find((string) $key);

        if ($agent === null) {
            return null;
        }

        return BrokerAgent::fromApi($agent);
    }
}
