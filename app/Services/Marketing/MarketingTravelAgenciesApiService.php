<?php

namespace App\Services\Marketing;

use Illuminate\Support\Collection;

class MarketingTravelAgenciesApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/travel-agencies';
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
            'representante' => trim($record['representante'] ?? ''),
            'email' => trim($record['email'] ?? ''),
        ]);
    }
}
