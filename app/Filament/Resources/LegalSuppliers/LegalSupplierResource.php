<?php

namespace App\Filament\Resources\LegalSuppliers;

use App\Filament\Resources\LegalSuppliers\Pages\ManageLegalSuppliers;
use App\Filament\Resources\LegalSuppliers\Pages\ViewLegalSupplier;
use App\Filament\Resources\LegalSuppliers\Schemas\LegalSupplierInfolist;
use App\Filament\Resources\LegalSuppliers\Tables\LegalSuppliersTable;
use App\Models\LegalSupplier;
use App\Services\Marketing\MarketingLegalSuppliersApiService;
use BackedEnum;
use Closure;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LegalSupplierResource extends Resource
{
    protected static ?string $model = LegalSupplier::class;

    protected static ?string $navigationLabel = 'Proveedores jurídicos';

    protected static ?string $modelLabel = 'proveedor jurídico';

    protected static ?string $pluralModelLabel = 'Proveedores jurídicos';

    protected static ?string $slug = 'proveedores-juridicos';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Audiencias TDG';

    protected static ?int $navigationSort = 28;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return LegalSupplierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalSuppliersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLegalSuppliers::route('/'),
            'view' => ViewLegalSupplier::route('/{record}'),
        ];
    }

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $supplier = app(MarketingLegalSuppliersApiService::class)->find((string) $key);

        if ($supplier === null) {
            return null;
        }

        return LegalSupplier::fromApi($supplier);
    }
}
