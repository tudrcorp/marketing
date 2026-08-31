<?php

namespace App\Services\Marketing;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MarketingApiHttpFactory
{
    public function client(?int $timeout = null): PendingRequest
    {
        return $this->makeClient($timeout ?? $this->timeout());
    }

    public function emailClient(?int $timeout = null): PendingRequest
    {
        return $this->makeClient($timeout ?? $this->emailTimeout());
    }

    private function makeClient(int $timeout): PendingRequest
    {
        $request = Http::timeout(max(1, $timeout))
            ->acceptJson();

        $apiKey = config('services.marketing_api.key');

        if (filled($apiKey)) {
            $request = $request->withHeaders([
                'X-API-Key' => (string) $apiKey,
            ]);
        }

        return $request;
    }

    private function timeout(): int
    {
        return (int) config('services.marketing_api.timeout', 5);
    }

    private function emailTimeout(): int
    {
        return (int) config('services.marketing_api.email_timeout', 120);
    }
}
