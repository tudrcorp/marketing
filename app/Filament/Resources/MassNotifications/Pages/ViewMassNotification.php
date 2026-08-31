<?php

namespace App\Filament\Resources\MassNotifications\Pages;

use App\Filament\Resources\MassNotifications\Actions\SendMassNotificationAction;
use App\Filament\Resources\MassNotifications\Actions\SendTestMassNotificationAction;
use App\Filament\Resources\MassNotifications\MassNotificationResource;
use App\Services\Marketing\DispatchProgressTracker;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class ViewMassNotification extends ViewRecord
{
    protected static string $resource = MassNotificationResource::class;

    public bool $shouldPollDelivery = false;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->syncDeliveryPolling();
    }

    #[On('dispatch-progress-started')]
    public function onDispatchProgressStarted(): void
    {
        $this->refreshDeliverySummary();
    }

    public function refreshDeliverySummary(): void
    {
        $this->record->refresh();
        $this->syncDeliveryPolling();
    }

    protected function getHeaderActions(): array
    {
        return [
            SendTestMassNotificationAction::make(),
            SendMassNotificationAction::make(),
            EditAction::make()->icon(Heroicon::OutlinedPencilSquare),
        ];
    }

    private function syncDeliveryPolling(): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            $this->shouldPollDelivery = false;

            return;
        }

        $this->shouldPollDelivery = app(DispatchProgressTracker::class)
            ->hasActiveRunsForNotification((int) $userId, (int) $this->record->getKey());
    }
}
