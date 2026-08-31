<?php

namespace App\Services\Marketing;

readonly class BirthdayNotificationBulkEmailResult
{
    /**
     * @param  array<string, mixed>|null  $apiTrace
     */
    public function __construct(
        public bool $successful,
        public int $sent,
        public int $total,
        public ?string $message = null,
        public bool $dryRun = false,
        public ?array $apiTrace = null,
    ) {}
}
