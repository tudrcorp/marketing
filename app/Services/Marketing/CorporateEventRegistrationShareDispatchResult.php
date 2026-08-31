<?php

namespace App\Services\Marketing;

class CorporateEventRegistrationShareDispatchResult
{
    /**
     * @param  list<CorporateEventRegistrationShareChannelResult>  $results
     */
    public function __construct(public array $results) {}

    public function allSuccessful(): bool
    {
        return collect($this->results)->every(
            fn (CorporateEventRegistrationShareChannelResult $result): bool => $result->successful,
        );
    }

    /**
     * @return list<CorporateEventRegistrationShareChannelResult>
     */
    public function failedResults(): array
    {
        return collect($this->results)
            ->filter(fn (CorporateEventRegistrationShareChannelResult $result): bool => ! $result->successful)
            ->values()
            ->all();
    }

    public function summary(): string
    {
        return collect($this->results)
            ->map(fn (CorporateEventRegistrationShareChannelResult $result): string => sprintf(
                '%s: %s',
                $result->channel->getLabel(),
                match (true) {
                    $result->queued => 'en cola',
                    $result->successful => 'enviado',
                    default => 'falló',
                },
            ))
            ->join(' · ');
    }

    public function hasQueued(): bool
    {
        return collect($this->results)->contains(
            fn (CorporateEventRegistrationShareChannelResult $result): bool => $result->queued,
        );
    }

    public function failureMessage(): ?string
    {
        $messages = collect($this->failedResults())
            ->map(fn (CorporateEventRegistrationShareChannelResult $result): string => sprintf(
                '%s — %s',
                $result->channel->getLabel(),
                $result->message,
            ))
            ->all();

        if ($messages === []) {
            return null;
        }

        return implode(' ', $messages);
    }
}
