<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithMarketingHealthCheck;
use App\Services\Marketing\HealthCheckResult;
use App\Services\Marketing\MarketingApiHealthService;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;

class DatabaseHealthWidget extends Widget
{
    use CanPoll;
    use InteractsWithMarketingHealthCheck;

    protected static ?int $sort = -9;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.marketing-health-status';

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    protected function resolveHealth(MarketingApiHealthService $service): HealthCheckResult
    {
        return $service->checkDatabase();
    }

    protected function getHealthTitle(): string
    {
        return 'Base de datos';
    }

    protected function getHealthDescription(): string
    {
        return 'Conectividad del API con la BD';
    }

    protected function getHealthIcon(): string
    {
        return 'heroicon-o-circle-stack';
    }
}
