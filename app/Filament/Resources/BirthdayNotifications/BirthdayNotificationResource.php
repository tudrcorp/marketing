<?php

namespace App\Filament\Resources\BirthdayNotifications;

use App\Filament\Resources\BirthdayNotifications\Pages\CreateBirthdayNotification;
use App\Filament\Resources\BirthdayNotifications\Pages\EditBirthdayNotification;
use App\Filament\Resources\BirthdayNotifications\Pages\ListBirthdayNotifications;
use App\Filament\Resources\BirthdayNotifications\Pages\ViewBirthdayNotification;
use App\Filament\Resources\BirthdayNotifications\Schemas\BirthdayNotificationForm;
use App\Filament\Resources\BirthdayNotifications\Schemas\BirthdayNotificationInfolist;
use App\Filament\Resources\BirthdayNotifications\Tables\BirthdayNotificationsTable;
use App\Models\BirthdayNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BirthdayNotificationResource extends Resource
{
    protected static ?string $model = BirthdayNotification::class;

    protected static ?string $navigationLabel = 'Notificaciones de cumpleaños';

    protected static ?string $modelLabel = 'notificación de cumpleaños';

    protected static ?string $pluralModelLabel = 'Notificaciones de cumpleaños';

    protected static ?string $slug = 'notificaciones-cumpleanos';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 14;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    public static function form(Schema $schema): Schema
    {
        return BirthdayNotificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BirthdayNotificationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BirthdayNotificationInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBirthdayNotifications::route('/'),
            'create' => CreateBirthdayNotification::route('/create'),
            'view' => ViewBirthdayNotification::route('/{record}'),
            'edit' => EditBirthdayNotification::route('/{record}/edit'),
        ];
    }
}
