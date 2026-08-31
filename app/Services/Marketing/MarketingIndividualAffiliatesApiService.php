<?php

namespace App\Services\Marketing;

class MarketingIndividualAffiliatesApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/affiliates';
    }
}
