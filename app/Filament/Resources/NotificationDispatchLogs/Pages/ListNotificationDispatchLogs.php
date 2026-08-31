<?php

namespace App\Filament\Resources\NotificationDispatchLogs\Pages;

use App\Filament\Resources\NotificationDispatchLogs\NotificationDispatchLogResource;
use App\Filament\Resources\NotificationDispatchLogs\Support\NotificationDispatchLogPresentation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ListNotificationDispatchLogs extends ListRecords
{
    protected static string $resource = NotificationDispatchLogResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.pages.notification-dispatch-log-stats')
                    ->viewData(fn (): array => [
                        'stats' => NotificationDispatchLogPresentation::todayStats(),
                    ]),
                $this->getTabsContentComponent(),
                EmbeddedTable::make(),
            ]);
    }
}
