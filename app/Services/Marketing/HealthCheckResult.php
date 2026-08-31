<?php

namespace App\Services\Marketing;

class HealthCheckResult
{
    public function __construct(
        public readonly bool $isHealthy,
        public readonly string $label,
        public readonly string $endpoint,
        public readonly ?int $statusCode = null,
        public readonly ?int $responseTimeMs = null,
        public readonly ?string $message = null,
    ) {}

    public function statusText(): string
    {
        return $this->isHealthy ? 'OK' : 'ERROR';
    }
}
