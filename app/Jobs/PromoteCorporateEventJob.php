<?php

namespace App\Jobs;

use App\Models\CorporateEvent;
use App\Models\User;
use App\Services\Marketing\CorporateEventPromotionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PromoteCorporateEventJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public int $corporateEventId,
        public array $channels,
        public int $sentById,
    ) {}

    public function handle(CorporateEventPromotionService $promotionService): void
    {
        $event = CorporateEvent::query()->find($this->corporateEventId);
        $user = User::query()->find($this->sentById);

        if ($event === null || $user === null) {
            return;
        }

        $promotionService->promote($event, $this->channels, $user);
    }
}
