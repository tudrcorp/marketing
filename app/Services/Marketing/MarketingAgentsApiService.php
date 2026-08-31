<?php

namespace App\Services\Marketing;

class MarketingAgentsApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/agents';
    }
}
