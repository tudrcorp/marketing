<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMarketingHealthCheck;
use App\Services\Marketing\HealthCheckResult;
use App\Services\Marketing\MarketingApiHealthService;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;

class ApiHealthWidget extends Widget
{
    use CanPoll;
    use InteractsWithMarketingHealthCheck;

    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.marketing-health-status';

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    protected function resolveHealth(MarketingApiHealthService $service): HealthCheckResult
    {
        return $service->checkApi();
    }

    protected function getHealthTitle(): string
    {
        return 'API Marketing';
    }

    protected function getHealthDescription(): string
    {
        return 'Estado del servicio principal';
    }

    protected function getHealthIcon(): string
    {
        return 'heroicon-o-server-stack';
    }
}
