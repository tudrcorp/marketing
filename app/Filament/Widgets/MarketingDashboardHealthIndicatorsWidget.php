<?php

namespace App\Filament\Widgets;

use App\Services\Marketing\HealthCheckResult;
use App\Services\Marketing\MarketingApiHealthService;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;

class MarketingDashboardHealthIndicatorsWidget extends Widget
{
    use CanPoll;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.marketing-dashboard-health-indicators';

    /**
     * @var array{
     *     isHealthy: bool,
     *     label: string,
     *     statusText: string,
     * }
     */
    public array $apiHealth = [
        'isHealthy' => false,
        'label' => 'API Marketing',
        'statusText' => 'ERROR',
    ];

    /**
     * @var array{
     *     isHealthy: bool,
     *     label: string,
     *     statusText: string,
     * }
     */
    public array $databaseHealth = [
        'isHealthy' => false,
        'label' => 'Base de datos',
        'statusText' => 'ERROR',
    ];

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    public function mount(MarketingApiHealthService $service): void
    {
        $this->refreshHealth($service);
    }

    public function refreshHealth(MarketingApiHealthService $service): void
    {
        $this->apiHealth = $this->formatHealth($service->checkApi(), 'API Marketing');
        $this->databaseHealth = $this->formatHealth($service->checkDatabase(), 'Base de datos');
    }

    /**
     * @return array{
     *     isHealthy: bool,
     *     label: string,
     *     statusText: string,
     * }
     */
    private function formatHealth(HealthCheckResult $result, string $label): array
    {
        return [
            'isHealthy' => $result->isHealthy,
            'label' => $label,
            'statusText' => $result->isHealthy ? 'OK' : 'ERROR',
        ];
    }
}
