<?php

namespace App\Filament\Resources\RrhhColaboradores;

use App\Filament\Resources\RrhhColaboradores\Pages\ManageRrhhColaboradores;
use App\Filament\Resources\RrhhColaboradores\Pages\ViewRrhhColaborador;
use App\Filament\Resources\RrhhColaboradores\Schemas\RrhhColaboradorInfolist;
use App\Filament\Resources\RrhhColaboradores\Tables\RrhhColaboradoresTable;
use App\Models\RrhhColaborador;
use App\Services\Marketing\MarketingRrhhColaboradoresApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RrhhColaboradorResource extends Resource
{
    protected static ?string $model = RrhhColaborador::class;

    protected static ?string $navigationLabel = 'Colaboradores';

    protected static ?string $modelLabel = 'colaborador';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    protected static ?string $slug = 'colaboradores';

    protected static ?string $recordTitleAttribute = 'fullName';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 26;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return RrhhColaboradorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RrhhColaboradoresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRrhhColaboradores::route('/'),
            'view' => ViewRrhhColaborador::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $colaborador = app(MarketingRrhhColaboradoresApiService::class)->find((string) $key);

        if ($colaborador === null) {
            return null;
        }

        return RrhhColaborador::fromApi($colaborador);
    }
}
