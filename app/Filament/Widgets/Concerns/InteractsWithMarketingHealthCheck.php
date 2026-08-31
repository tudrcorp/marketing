<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\Marketing\HealthCheckResult;
use App\Services\Marketing\MarketingApiHealthService;
trait InteractsWithMarketingHealthCheck
{
    protected int | string | array $columnSpan = 1;

    /**
     * @var array{
     *     isHealthy: bool,
     *     label: string,
     *     endpoint: string,
     *     statusCode: int|null,
     *     responseTimeMs: int|null,
     *     message: string|null,
     * }
     */
    public array $health = [
        'isHealthy' => false,
        'label' => '',
        'endpoint' => '',
        'statusCode' => null,
        'responseTimeMs' => null,
        'message' => null,
    ];

    abstract protected function resolveHealth(MarketingApiHealthService $service): HealthCheckResult;

    abstract protected function getHealthTitle(): string;

    abstract protected function getHealthDescription(): string;

    abstract protected function getHealthIcon(): string;

    public function mount(MarketingApiHealthService $service): void
    {
        $this->refreshHealth($service);
    }

    public function refreshHealth(MarketingApiHealthService $service): void
    {
        $result = $this->resolveHealth($service);

        $this->health = [
            'isHealthy' => $result->isHealthy,
            'label' => $result->label,
            'endpoint' => $result->endpoint,
            'statusCode' => $result->statusCode,
            'responseTimeMs' => $result->responseTimeMs,
            'message' => $result->message,
        ];
    }

    public function healthStatusText(): string
    {
        return $this->health['isHealthy'] ? 'OK' : 'ERROR';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'title' => $this->getHealthTitle(),
            'description' => $this->getHealthDescription(),
            'icon' => $this->getHealthIcon(),
            'pollingInterval' => $this->getPollingInterval(),
        ];
    }
}
