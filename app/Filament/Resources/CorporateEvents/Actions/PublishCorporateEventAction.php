<?php

namespace App\Filament\Resources\CorporateEvents\Actions;

use App\Marketing\CorporateEventStatus;
use App\Models\CorporateEvent;
use App\Services\Marketing\CorporateEventPromotionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class PublishCorporateEventAction
{
    public static function make(): Action
    {
        return Action::make('publish')
            ->label('Publicar')
            ->icon(Heroicon::OutlinedMegaphone)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publicar evento')
            ->modalDescription('El evento quedará visible y listo para promocionar.')
            ->authorize('publish')
            ->visible(fn (CorporateEvent $record): bool => $record->statusEnum() === CorporateEventStatus::Draft)
            ->action(function (CorporateEvent $record, CorporateEventPromotionService $promotionService): void {
                $promotionService->publish($record);

                Notification::make()
                    ->title('Evento publicado')
                    ->body('Ya puedes promocionarlo a las audiencias configuradas.')
                    ->success()
                    ->send();
            });
    }
}
