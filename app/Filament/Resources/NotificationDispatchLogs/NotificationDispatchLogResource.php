<?php

namespace App\Filament\Resources\NotificationDispatchLogs;

use App\Filament\Resources\NotificationDispatchLogs\Pages\ListNotificationDispatchLogs;
use App\Filament\Resources\NotificationDispatchLogs\Pages\ViewNotificationDispatchLog;
use App\Filament\Resources\NotificationDispatchLogs\Schemas\NotificationDispatchLogInfolist;
use App\Filament\Resources\NotificationDispatchLogs\Tables\NotificationDispatchLogsTable;
use App\Models\NotificationDispatchLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NotificationDispatchLogResource extends Resource
{
    protected static ?string $model = NotificationDispatchLog::class;

    protected static ?string $navigationLabel = 'Historial de envíos';

    protected static ?string $modelLabel = 'registro de envío';

    protected static ?string $pluralModelLabel = 'Historial de envíos';

    protected static ?string $slug = 'historial-envios';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 16;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NotificationDispatchLogsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotificationDispatchLogInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationDispatchLogs::route('/'),
            'view' => ViewNotificationDispatchLog::route('/{record}'),
        ];
    }
}
