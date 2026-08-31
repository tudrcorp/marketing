<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;

readonly class MassNotificationChannelResult
{
    /**
     * @param  array<string, mixed>|null  $apiTrace
     */
    public function __construct(
        public BirthdayNotificationChannel $channel,
        public bool $successful,
        public int $sent,
        public int $total,
        public string $message,
        public ?array $apiTrace = null,
    ) {}
}
