<?php

namespace App\Filament\Resources\NaturalSuppliers;

use App\Filament\Resources\NaturalSuppliers\Pages\ManageNaturalSuppliers;
use App\Filament\Resources\NaturalSuppliers\Pages\ViewNaturalSupplier;
use App\Filament\Resources\NaturalSuppliers\Schemas\NaturalSupplierInfolist;
use App\Filament\Resources\NaturalSuppliers\Tables\NaturalSuppliersTable;
use App\Models\NaturalSupplier;
use App\Services\Marketing\MarketingNaturalSuppliersApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class NaturalSupplierResource extends Resource
{
    protected static ?string $model = NaturalSupplier::class;

    protected static ?string $navigationLabel = 'Proveedores naturales';

    protected static ?string $modelLabel = 'proveedor natural';

    protected static ?string $pluralModelLabel = 'Proveedores naturales';

    protected static ?string $slug = 'proveedores-naturales';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 27;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return NaturalSupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NaturalSuppliersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNaturalSuppliers::route('/'),
            'view' => ViewNaturalSupplier::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $supplier = app(MarketingNaturalSuppliersApiService::class)->find((string) $key);

        if ($supplier === null) {
            return null;
        }

        return NaturalSupplier::fromApi($supplier);
    }
}
