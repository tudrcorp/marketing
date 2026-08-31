<?php

namespace App\Services\Marketing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class MarketingRrhhColaboradoresApiService extends MarketingPaginatedApiService
{
    private const int ApiPageSize = 10;

    protected function apiPath(): string
    {
        return '/api/rrhh-colaboradores';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        return $this->allRecords()->firstWhere('id', $id);
    }

    /**
     * The marketing API exposes a fixed page size of 10 and ignores `limit` / `search`.
     * Aggregate every page locally so Filament can paginate, search and change page size.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 10,
        ?string $search = null,
        ?string $sortColumn = null,
        ?string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        $perPage = max(1, $perPage);
        $page = max(1, $page);

        $records = $this->filterRecords($this->allRecords(), $search);
        $records = $this->sortRecords(
            $records,
            $sortColumn ?? 'fullName',
            $sortDirection ?? 'asc',
        );

        $total = $records->count();
        $items = $records
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function allRecords(): Collection
    {
        return once(function (): Collection {
            try {
                $page = 1;
                $records = collect();

                do {
                    $response = app(MarketingApiHttpFactory::class)
                        ->client($this->timeout())
                        ->get($this->endpoint($this->apiPath()), [
                            'page' => $page,
                        ]);

                    if (! $response->successful()) {
                        return collect();
                    }

                    /** @var array{data?: array<int, array<string, mixed>>, pagination?: array{page?: int, totalPages?: int, total?: int}} $payload */
                    $payload = $response->json() ?? [];
                    $pageRecords = $this->normalizeRecords(collect($payload['data'] ?? []));
                    $records = $records->concat($pageRecords);

                    $pagination = $payload['pagination'] ?? [];
                    $totalPages = max(
                        1,
                        (int) ($pagination['totalPages'] ?? (int) ceil(((int) ($pagination['total'] ?? $pageRecords->count())) / self::ApiPageSize)),
                    );

                    $page++;
                } while ($page <= $totalPages && $pageRecords->isNotEmpty());

                return $records->values();
            } catch (ConnectionException) {
                return collect();
            } catch (Throwable) {
                return collect();
            }
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    private function filterRecords(Collection $records, ?string $search): Collection
    {
        if (! filled($search)) {
            return $records;
        }

        $term = mb_strtolower(trim($search));

        return $records
            ->filter(function (array $record) use ($term): bool {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($record['fullName'] ?? ''),
                    (string) ($record['emailCorporativo'] ?? ''),
                    (string) ($record['emailAlternativo'] ?? ''),
                    (string) ($record['emailPersonal'] ?? ''),
                    (string) ($record['telefono'] ?? ''),
                    (string) ($record['telefonoCorporativo'] ?? ''),
                ]));

                return str_contains($haystack, $term);
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeRecords(Collection $records): Collection
    {
        return parent::normalizeRecords($records)->map(fn (array $record): array => [
            ...$record,
            'fullName' => trim($record['fullName'] ?? ''),
            'emailCorporativo' => trim($record['emailCorporativo'] ?? ''),
            'emailAlternativo' => trim($record['emailAlternativo'] ?? ''),
            'emailPersonal' => trim($record['emailPersonal'] ?? ''),
        ]);
    }
}
