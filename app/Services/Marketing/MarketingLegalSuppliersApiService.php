<?php

namespace App\Services\Marketing;

use Illuminate\Support\Collection;

class MarketingLegalSuppliersApiService extends MarketingPaginatedApiService
{
    protected function apiPath(): string
    {
        return '/api/suppliers';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeRecords(Collection $records): Collection
    {
        return $records->map(fn (array $record): array => [
            ...$record,
            'name' => trim($record['name'] ?? ''),
            'razon_social' => trim($record['razon_social'] ?? ''),
            'rif' => trim($record['rif'] ?? ''),
            'correo_principal' => trim($record['correo_principal'] ?? ''),
            'id' => $this->resolveRecordId($record),
        ]);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function resolveRecordId(array $record): string
    {
        if (filled($record['rif'] ?? null)) {
            return trim((string) $record['rif']);
        }

        $fingerprint = implode('|', [
            mb_strtolower(trim($record['name'] ?? '')),
            mb_strtolower(trim($record['razon_social'] ?? '')),
            trim($record['correo_principal'] ?? ''),
            trim($record['local_phone'] ?? ''),
            trim($record['personal_phone'] ?? ''),
        ]);

        return 'supplier-'.hash('xxh128', $fingerprint);
    }
}
