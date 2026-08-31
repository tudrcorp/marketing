<?php

namespace App\Services\Marketing;

use Illuminate\Support\Collection;

class MarketingNaturalSuppliersApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/doctor-nurses';
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
            'razon_social' => trim($record['razon_social'] ?? ''),
            'rif' => trim($record['rif'] ?? ''),
            'correo_principal' => trim($record['correo_principal'] ?? ''),
        ]);
    }
}
