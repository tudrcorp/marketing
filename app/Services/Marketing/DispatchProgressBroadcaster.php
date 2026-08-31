<?php

namespace App\Services\Marketing;

use App\Filament\Widgets\MarketingDispatchProgressFloaterWidget;
use Livewire\Livewire;

class DispatchProgressBroadcaster
{
    public static function started(): void
    {
        if (! class_exists(Livewire::class) || ! app()->bound('livewire')) {
            return;
        }

        try {
            Livewire::dispatch('dispatch-progress-started')
                ->to(MarketingDispatchProgressFloaterWidget::class);
        } catch (\Throwable) {
            // El widget se actualizará en el siguiente poll.
        }
    }
}
