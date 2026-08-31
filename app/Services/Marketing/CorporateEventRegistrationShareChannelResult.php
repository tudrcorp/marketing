<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;

class CorporateEventRegistrationShareChannelResult
{
    /**
     * @param  array<string, mixed>|null  $apiTrace
     */
    public function __construct(
        public BirthdayNotificationChannel $channel,
        public bool $successful,
        public string $message,
        public ?array $apiTrace = null,
        public bool $queued = false,
    ) {}
}
