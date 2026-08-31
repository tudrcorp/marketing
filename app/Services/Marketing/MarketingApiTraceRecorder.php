<?php

namespace App\Services\Marketing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

class MarketingApiTraceRecorder
{
    /**
     * @param  array<string, mixed>  $request
     * @return array{api_calls: list<array<string, mixed>>}
     */
    public function fromResponse(
        string $label,
        string $endpoint,
        string $method,
        array $request,
        Response $response,
    ): array {
        return [
            'api_calls' => [
                $this->call(
                    label: $label,
                    endpoint: $endpoint,
                    method: $method,
                    request: $request,
                    status: $response->status(),
                    body: $this->parseBody($response),
                    successful: $response->successful(),
                ),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array{api_calls: list<array<string, mixed>>}
     */
    public function fromConnectionError(
        string $label,
        string $endpoint,
        string $method,
        array $request,
        ConnectionException $exception,
    ): array {
        return [
            'api_calls' => [
                $this->call(
                    label: $label,
                    endpoint: $endpoint,
                    method: $method,
                    request: $request,
                    status: null,
                    body: null,
                    successful: false,
                    error: [
                        'type' => 'connection',
                        'message' => $exception->getMessage(),
                    ],
                ),
            ],
        ];
    }

    /**
     * Distingue un timeout (la conexión se estableció pero no llegó respuesta a tiempo,
     * probablemente porque el API externo procesa el envío antes de responder) de un
     * error de conectividad real (host inalcanzable, conexión rechazada).
     */
    public function isTimeout(ConnectionException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'curl error 28') || str_contains($message, 'operation timed out');
    }

    /**
     * @return array{api_calls: list<array<string, mixed>>, notes: string}
     */
    public function fromLocalValidation(string $reason): array
    {
        return [
            'api_calls' => [],
            'notes' => $reason,
        ];
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    public function sanitizeRequest(array $request): array
    {
        $sanitized = [];

        foreach ($request as $key => $value) {
            if (in_array($key, ['copy', 'html'], true) && is_string($value)) {
                $sanitized[$key.'_preview'] = mb_substr($value, 0, 280).(mb_strlen($value) > 280 ? '…' : '');
                $sanitized[$key.'_length'] = mb_strlen($value);

                continue;
            }

            if ($key === 'attachment_base64' && is_string($value)) {
                $sanitized['attachment_base64_included'] = true;
                $sanitized['attachment_base64_length'] = mb_strlen($value);

                continue;
            }

            if ($key === 'recipients' && is_string($value)) {
                $decoded = json_decode($value, true);
                $sanitized[$key] = is_array($decoded) ? $decoded : $value;

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $request
     * @param  array<string, mixed>|string|null  $body
     * @param  array<string, mixed>|null  $error
     * @return array<string, mixed>
     */
    private function call(
        string $label,
        string $endpoint,
        string $method,
        array $request,
        ?int $status,
        array|string|null $body,
        bool $successful,
        ?array $error = null,
    ): array {
        return [
            'service' => 'marketing_api',
            'label' => $label,
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'request' => $this->sanitizeRequest($request),
            'response' => $error === null ? [
                'status' => $status,
                'successful' => $successful,
                'body' => $body,
            ] : null,
            'error' => $error,
        ];
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function parseBody(Response $response): array|string|null
    {
        $json = $response->json();

        if (is_array($json)) {
            return $json;
        }

        $body = trim($response->body());

        return filled($body) ? $body : null;
    }
}
