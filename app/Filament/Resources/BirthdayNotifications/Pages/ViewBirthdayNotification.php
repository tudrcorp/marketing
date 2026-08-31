<?php

namespace App\Filament\Resources\BirthdayNotifications\Pages;

use App\Filament\Resources\BirthdayNotifications\Actions\SendTestBirthdayNotificationAction;
use App\Filament\Resources\BirthdayNotifications\BirthdayNotificationResource;
use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewBirthdayNotification extends ViewRecord
{
    protected static string $resource = BirthdayNotificationResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $record = $this->getRecord();
        $stats = BirthdayNotificationPresentation::stats($record);

        return sprintf(
            '%d canales · %d audiencias · %s caracteres',
            $stats['channels'],
            $stats['audiences'],
            number_format($stats['copy_length']),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            SendTestBirthdayNotificationAction::make(),
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(BirthdayNotificationResource::getUrl('index')),
            EditAction::make()
                ->icon(Heroicon::OutlinedPencilSquare),
            DeleteAction::make(),
        ];
    }
}
