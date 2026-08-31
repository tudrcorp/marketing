<?php

namespace App\Services\Marketing;

class MarketingAgenciesApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/agencies';
    }
}
