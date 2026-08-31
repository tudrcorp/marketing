<?php

namespace App\Filament\Resources\CorporateEvents\Actions;

use App\Filament\Support\ChannelToggleButtons;
use App\Jobs\PromoteCorporateEventJob;
use App\Models\CorporateEvent;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PromoteCorporateEventAction
{
    public static function make(): Action
    {
        return Action::make('promote')
            ->label('Promocionar')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('primary')
            ->modalHeading('Promocionar evento')
            ->modalDescription('Envía la invitación a los grupos destinatarios configurados en el evento.')
            ->modalSubmitActionLabel('Enviar invitación')
            ->modalWidth(Width::Large)
            ->schema([
                ChannelToggleButtons::make('channels', hiddenLabel: true),
            ])
            ->authorize('promote')
            ->visible(fn (CorporateEvent $record): bool => $record->statusEnum()->isPromotable())
            ->action(function (CorporateEvent $record, array $data): void {
                $user = Filament::auth()->user();

                if (! $user instanceof User) {
                    return;
                }

                PromoteCorporateEventJob::dispatch(
                    corporateEventId: $record->getKey(),
                    channels: $data['channels'] ?? [],
                    sentById: $user->getKey(),
                );

                Notification::make()
                    ->title('Promoción en cola')
                    ->body('La invitación se está enviando a los grupos destinatarios.')
                    ->success()
                    ->send();
            });
    }
}
