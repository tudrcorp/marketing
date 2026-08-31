<?php

namespace App\Services\Marketing;

use Illuminate\Http\Client\ConnectionException;
use Throwable;

class MarketingApiHealthService
{
    public function checkApi(): HealthCheckResult
    {
        return $this->check(
            endpoint: $this->endpoint('/api/health'),
            label: 'API Marketing',
        );
    }

    public function checkDatabase(): HealthCheckResult
    {
        return $this->check(
            endpoint: $this->endpoint('/api/health/db'),
            label: 'Base de datos API',
        );
    }

    private function check(string $endpoint, string $label): HealthCheckResult
    {
        $startedAt = microtime(true);

        try {
            $response = app(MarketingApiHttpFactory::class)
                ->client($this->timeout())
                ->get($endpoint);

            $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

            return new HealthCheckResult(
                isHealthy: $response->successful(),
                label: $label,
                endpoint: $endpoint,
                statusCode: $response->status(),
                responseTimeMs: $responseTimeMs,
                message: $this->resolveMessage($response->json(), $response->body()),
            );
        } catch (ConnectionException $exception) {
            return new HealthCheckResult(
                isHealthy: false,
                label: $label,
                endpoint: $endpoint,
                message: 'No se pudo conectar con el API.',
            );
        } catch (Throwable $exception) {
            return new HealthCheckResult(
                isHealthy: false,
                label: $label,
                endpoint: $endpoint,
                message: 'Error al verificar el estado del servicio.',
            );
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim(config('services.marketing_api.base_url'), '/').$path;
    }

    private function timeout(): int
    {
        return (int) config('services.marketing_api.timeout', 5);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveMessage(?array $payload, string $body): ?string
    {
        if (is_array($payload)) {
            foreach (['message', 'status', 'detail'] as $key) {
                if (filled($payload[$key] ?? null)) {
                    return (string) $payload[$key];
                }
            }
        }

        return filled($body) ? $body : null;
    }
}
