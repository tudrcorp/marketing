<?php

namespace App\Filament\Resources\NotificationDispatchLogs\Pages;

use App\Filament\Resources\NotificationDispatchLogs\NotificationDispatchLogResource;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use App\Services\Marketing\NotificationDispatchFailureInspector;
use App\Services\Marketing\NotificationDispatchRetryService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Throwable;

class ViewNotificationDispatchLog extends ViewRecord
{
    protected static string $resource = NotificationDispatchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retryDispatch')
                ->label('Reintentar envío')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reintentar destinatarios fallidos')
                ->modalDescription(function (NotificationDispatchLog $record): string {
                    $inspection = app(NotificationDispatchFailureInspector::class)->inspect($record);
                    $count = count($inspection['failures']);

                    return "Se reenviará la campaña «{$record->title}» a {$count} destinatario".($count === 1 ? '' : 's').' que fallaron. El resultado quedará como un nuevo registro en el historial.';
                })
                ->modalSubmitActionLabel('Sí, reintentar ahora')
                ->authorize('retry')
                ->visible(function (NotificationDispatchLog $record): bool {
                    return app(NotificationDispatchFailureInspector::class)->inspect($record)['can_retry'];
                })
                ->action(function (NotificationDispatchLog $record, NotificationDispatchRetryService $retryService): void {
                    /** @var User $sentBy */
                    $sentBy = auth()->user();

                    try {
                        $result = $retryService->retry($record, $sentBy);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->title('No se pudo reintentar')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Error al reintentar el envío')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($result->successful && $result->sent >= $result->total) {
                        Notification::make()
                            ->title('Reintento completado')
                            ->body($result->message)
                            ->success()
                            ->send();
                    } elseif ($result->sent > 0) {
                        Notification::make()
                            ->title('Reintento parcial')
                            ->body($result->message)
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('El reintento volvió a fallar')
                            ->body($result->message)
                            ->danger()
                            ->send();
                    }

                    $this->redirect(NotificationDispatchLogResource::getUrl('index'));
                }),
            Action::make('back')
                ->label('Volver al listado')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(NotificationDispatchLogResource::getUrl('index')),
        ];
    }
}
