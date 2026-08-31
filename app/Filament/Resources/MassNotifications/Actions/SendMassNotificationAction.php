<?php

namespace App\Filament\Resources\MassNotifications\Actions;

use App\Filament\Widgets\MarketingDispatchProgressFloaterWidget;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\MassEmailDispatchPace;
use App\Services\Marketing\MassNotificationDispatchService;
use App\Services\Marketing\QueueWorkerHealthInspector;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class SendMassNotificationAction
{
    public static function make(): Action
    {
        return Action::make('sendMassNotification')
            ->label('Ejecutar envío masivo')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Ejecutar envío masivo')
            ->modalDescription(function (MassNotification $record): string {
                $description = 'Se enviará «'.$record->title.'» por '
                    .collect($record->channelEnums())->map->getLabel()->join(', ')
                    .' a los grupos/destinatarios configurados. El resultado quedará en el historial de envíos.';

                if (! in_array(BirthdayNotificationChannel::Email, $record->channelEnums(), true)) {
                    return $description;
                }

                return $description.' '.MassEmailDispatchPace::analystWarning();
            })
            ->modalSubmitActionLabel('Sí, enviar ahora')
            ->visible(fn (MassNotification $record): bool => Gate::check('send', $record)
                && filled($record->copy)
                && $record->channelEnums() !== []
                && $record->audienceEnums() !== [])
            ->disabled(fn (MassNotification $record): bool => self::hasActiveRun($record))
            ->tooltip(fn (MassNotification $record): ?string => self::hasActiveRun($record)
                ? 'Ya hay un envío en curso para esta notificación. Espera a que termine para evitar duplicados.'
                : null)
            ->authorize(fn (MassNotification $record): bool => Gate::check('send', $record))
            ->action(function (
                Action $action,
                MassNotification $record,
                MassNotificationDispatchService $dispatchService,
            ): void {
                if (self::hasActiveRun($record)) {
                    Notification::make()
                        ->title('Ya hay un envío en curso')
                        ->body('Espera a que termine el envío anterior de esta notificación antes de reintentar, para evitar duplicados.')
                        ->warning()
                        ->send();

                    return;
                }

                $queueWarning = app(QueueWorkerHealthInspector::class)->unavailableMessage();

                if ($queueWarning !== null) {
                    Notification::make()
                        ->title('No se encoló: no hay worker de colas activo')
                        ->body($queueWarning)
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                /** @var User $sentBy */
                $sentBy = auth()->user();

                $result = $dispatchService->dispatchExisting($record, $sentBy);
                $livewire = $action->getLivewire();

                if ($result->dispatchRunId !== null && $livewire !== null) {
                    $livewire->dispatch('dispatch-progress-started')
                        ->to(MarketingDispatchProgressFloaterWidget::class);
                    $livewire->dispatch('dispatch-progress-started');
                }

                if ($result->queued) {
                    Notification::make()
                        ->title('Envío masivo iniciado')
                        ->body('El panel de progreso mostrará el avance en tiempo real.')
                        ->success()
                        ->send();

                    if (method_exists($livewire, 'refreshDeliverySummary')) {
                        $livewire->refreshDeliverySummary();
                    }

                    return;
                }

                if (method_exists($livewire, 'refreshDeliverySummary')) {
                    $livewire->refreshDeliverySummary();
                }

                if ($result->allSuccessful()) {
                    Notification::make()
                        ->title('Envío masivo completado')
                        ->body($result->summary())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Envío masivo con errores')
                    ->body($result->failureMessage() ?? $result->summary())
                    ->warning()
                    ->send();
            });
    }

    private static function hasActiveRun(MassNotification $record): bool
    {
        $userId = auth()->id();

        if ($userId === null) {
            return false;
        }

        return app(DispatchProgressTracker::class)
            ->hasActiveRunsForNotification((int) $userId, (int) $record->getKey());
    }
}
