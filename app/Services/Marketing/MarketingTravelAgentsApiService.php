<?php

namespace App\Services\Marketing;

use Illuminate\Support\Collection;

class MarketingTravelAgentsApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/travel-agents';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeRecords(Collection $records): Collection
    {
        return parent::normalizeRecords($records)->map(fn (array $record): array => [
            ...$record,
            'name' => trim($record['name'] ?? ''),
            'email' => trim($record['email'] ?? ''),
        ]);
    }
}
