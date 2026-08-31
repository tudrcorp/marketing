<?php

namespace App\Filament\Resources\MarketingRoles\Schemas;

use App\Filament\Forms\Components\MarketingRolePermissionsPicker;
use App\Models\MarketingRole;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MarketingRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del rol')
                    ->description('Identifica el perfil y su propósito dentro del panel de marketing.')
                    ->schema([
                        Grid::make()
                            ->columns([
                                'default' => 1,
                                'lg' => 2,
                            ])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre del rol')
                                    ->required()
                                    ->maxLength(120)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                                TextInput::make('slug')
                                    ->label('Identificador')
                                    ->required()
                                    ->maxLength(120)
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn (?MarketingRole $record): bool => $record?->is_system ?? false)
                                    ->helperText('Se usa internamente para validaciones y auditoría.'),
                            ]),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Describe qué puede hacer este rol en el día a día del equipo.'),
                        Toggle::make('is_system')
                            ->label('Rol del sistema')
                            ->helperText('Los roles del sistema vienen precargados y no pueden eliminarse.')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->columnSpanFull(),
                    ]),
                Section::make('Permisos del rol')
                    ->description('Activa módulos completos o permisos específicos según las responsabilidades del analista.')
                    ->schema([
                        MarketingRolePermissionsPicker::make('permissions')
                            ->hiddenLabel()
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
