<?php

namespace App\Services\Marketing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

abstract class MarketingPaginatedApiService
{
    abstract protected function apiPath(): string;

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $page = 1;

        do {
            $paginator = $this->paginate(page: $page, perPage: 10);
            $record = collect($paginator->items())->firstWhere('id', $id);

            if ($record !== null) {
                return $record;
            }

            $page++;
        } while ($page <= $paginator->lastPage());

        return null;
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 10,
        ?string $search = null,
        ?string $sortColumn = null,
        ?string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        try {
            $response = app(MarketingApiHttpFactory::class)
                ->client($this->timeout())
                ->get($this->endpoint($this->apiPath()), array_filter([
                    'page' => $page,
                    'limit' => $perPage,
                    'search' => $search,
                    'sort' => $sortColumn,
                    'order' => strtolower($sortDirection ?? 'asc'),
                ], fn (mixed $value): bool => filled($value)));

            if (! $response->successful()) {
                return $this->emptyPaginator($page, $perPage);
            }

            /** @var array{data?: array<int, array<string, mixed>>, pagination?: array{page?: int, limit?: int, total?: int}} $payload */
            $payload = $response->json();

            $records = $this->sortRecords(
                $this->normalizeRecords(collect($payload['data'] ?? [])),
                $sortColumn,
                $sortDirection,
            );
            $pagination = $payload['pagination'] ?? [];

            return new LengthAwarePaginator(
                items: $records,
                total: (int) ($pagination['total'] ?? $records->count()),
                perPage: (int) ($pagination['limit'] ?? $perPage),
                currentPage: (int) ($pagination['page'] ?? $page),
            );
        } catch (ConnectionException) {
            return $this->emptyPaginator($page, $perPage);
        } catch (Throwable) {
            return $this->emptyPaginator($page, $perPage);
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    protected function sortRecords(Collection $records, ?string $sortColumn, ?string $sortDirection): Collection
    {
        if (! filled($sortColumn)) {
            return $records;
        }

        $descending = strtolower($sortDirection ?? 'asc') === 'desc';

        return $records->sortBy(
            fn (array $record): string => mb_strtolower(trim((string) ($record[$sortColumn] ?? ''))),
            SORT_NATURAL,
            $descending,
        )->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    protected function normalizeRecords(Collection $records): Collection
    {
        return $records->map(fn (array $record): array => [
            ...$record,
            'id' => (string) ($record['id'] ?? ''),
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function emptyPaginator(int $page, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, $page);
    }

    protected function endpoint(string $path): string
    {
        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    protected function timeout(): int
    {
        return (int) config('services.marketing_api.timeout', 5);
    }
}
