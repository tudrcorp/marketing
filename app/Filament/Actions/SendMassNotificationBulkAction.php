<?php

namespace App\Filament\Actions;

use App\Filament\Support\MassNotificationCampaignSchema;
use App\Filament\Widgets\MarketingDispatchProgressFloaterWidget;
use App\Marketing\BirthdayNotificationAudience;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\MassNotificationDispatchService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class SendMassNotificationBulkAction
{
    public static function make(BirthdayNotificationAudience $audience): BulkAction
    {
        return BulkAction::make('sendMassNotification')
            ->label('Enviar notificación masiva')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->modalHeading('Enviar notificación masiva')
            ->modalDescription(fn (BulkAction $action): string => 'Configura el mensaje que recibirán '
                .$action->getSelectedRecords()->count()
                .' destinatario'
                .($action->getSelectedRecords()->count() === 1 ? '' : 's')
                .' seleccionado'
                .($action->getSelectedRecords()->count() === 1 ? '' : 's')
                .'.')
            ->modalIcon(Heroicon::OutlinedBellAlert)
            ->modalIconColor('gray')
            ->modalSubmitActionLabel('Enviar notificación')
            ->modalCancelActionLabel('Cancelar')
            ->modalWidth(Width::FourExtraLarge)
            ->extraModalWindowAttributes(['class' => 'marketing-mass-notification-bulk-modal'])
            ->schema(MassNotificationCampaignSchema::sections())
            ->authorize(fn (): bool => Gate::check('create', MassNotification::class))
            ->action(function (
                BulkAction $action,
                array $data,
                Collection $records,
                MassNotificationDispatchService $dispatchService,
            ) use ($audience): void {
                /** @var User $sentBy */
                $sentBy = auth()->user();

                $result = $dispatchService->dispatch(
                    audience: $audience,
                    selectedRecords: $records,
                    data: $data,
                    sentBy: $sentBy,
                );

                $livewire = $action->getLivewire();

                if ($result->dispatchRunId !== null && $livewire !== null) {
                    $livewire->dispatch('dispatch-progress-started')
                        ->to(MarketingDispatchProgressFloaterWidget::class);
                }

                if ($result->queued) {
                    Notification::make()
                        ->title('Envío iniciado')
                        ->body('El panel de progreso mostrará el avance en tiempo real.')
                        ->success()
                        ->send();

                    return;
                }

                if ($result->allSuccessful()) {
                    Notification::make()
                        ->title('Notificación masiva enviada')
                        ->body($result->summary())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Notificación masiva con errores')
                    ->body($result->failureMessage() ?? $result->summary())
                    ->warning()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
