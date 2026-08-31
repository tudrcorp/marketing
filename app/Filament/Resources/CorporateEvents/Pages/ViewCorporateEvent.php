<?php

namespace App\Filament\Resources\CorporateEvents\Pages;

use App\Filament\Resources\CorporateEvents\Actions\PromoteCorporateEventAction;
use App\Filament\Resources\CorporateEvents\Actions\PublishCorporateEventAction;
use App\Filament\Resources\CorporateEvents\Actions\ShareCorporateEventRegistrationAction;
use App\Filament\Resources\CorporateEvents\CorporateEventResource;
use App\Filament\Resources\CorporateEvents\Support\CorporateEventPresentation;
use App\Marketing\CorporateEventStatus;
use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventPromotionService;
use App\Services\Marketing\CorporateEventRegistrationUrlService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewCorporateEvent extends ViewRecord
{
    protected static string $resource = CorporateEventResource::class;

    protected function resolveRecord(int|string $key): CorporateEvent
    {
        /** @var CorporateEvent $record */
        $record = parent::resolveRecord($key);

        return app(CorporateEventRegistrationUrlService::class)->ensureForEvent($record);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->getRecordTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $stats = CorporateEventPresentation::stats($this->getRecord());

        return sprintf(
            '%d audiencias · %s inscritos · %s',
            $stats['audiences'],
            $stats['capacity_label'],
            $stats['attendance_rate'] === null
                ? 'sin métricas de asistencia'
                : $stats['attendance_rate'].'% asistencia',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ShareCorporateEventRegistrationAction::make(),
            PublishCorporateEventAction::make(),
            PromoteCorporateEventAction::make(),
            Action::make('markInProgress')
                ->label('Marcar en curso')
                ->icon(Heroicon::OutlinedPlayCircle)
                ->color('warning')
                ->visible(fn (CorporateEvent $record): bool => in_array($record->statusEnum(), [
                    CorporateEventStatus::Published,
                    CorporateEventStatus::Promoted,
                ], true))
                ->authorize('update')
                ->action(function (CorporateEvent $record, CorporateEventPromotionService $service): void {
                    $service->markInProgress($record);

                    Notification::make()->title('Evento en curso')->success()->send();
                }),
            Action::make('complete')
                ->label('Finalizar')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (CorporateEvent $record): bool => $record->statusEnum() === CorporateEventStatus::InProgress)
                ->authorize('update')
                ->action(function (CorporateEvent $record, CorporateEventPromotionService $service): void {
                    $service->complete($record);

                    Notification::make()->title('Evento finalizado')->success()->send();
                }),
            Action::make('cancel')
                ->label('Cancelar evento')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (CorporateEvent $record): bool => ! in_array($record->statusEnum(), [
                    CorporateEventStatus::Completed,
                    CorporateEventStatus::Cancelled,
                ], true))
                ->authorize('update')
                ->action(function (CorporateEvent $record, CorporateEventPromotionService $service): void {
                    $service->cancel($record);

                    Notification::make()->title('Evento cancelado')->warning()->send();
                }),
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(CorporateEventResource::getUrl('index')),
            EditAction::make()->icon(Heroicon::OutlinedPencilSquare),
            DeleteAction::make(),
        ];
    }
}
