<?php

namespace App\Services\Marketing;

use Illuminate\Support\Collection;

class MarketingCorporateAffiliatesApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/affiliate-corporates';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeRecords(Collection $records): Collection
    {
        return parent::normalizeRecords($records)->map(fn (array $record): array => [
            ...$record,
            'first_name' => trim($record['first_name'] ?? ''),
        ]);
    }
}
