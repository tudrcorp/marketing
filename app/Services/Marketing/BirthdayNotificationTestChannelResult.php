<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;

class BirthdayNotificationTestChannelResult
{
    /**
     * @param  array<string, mixed>|null  $apiTrace
     */
    public function __construct(
        public BirthdayNotificationChannel $channel,
        public bool $successful,
        public ?string $message = null,
        public ?array $apiTrace = null,
    ) {}
}
