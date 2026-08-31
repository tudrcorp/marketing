<?php

namespace App\Services\Marketing;

class CorporateEventRegistrationShareResult
{
    /**
     * @param  array<string, mixed>|null  $apiTrace
     */
    public function __construct(
        public bool $successful,
        public string $message,
        public ?array $apiTrace = null,
        public bool $queued = false,
    ) {}
}
