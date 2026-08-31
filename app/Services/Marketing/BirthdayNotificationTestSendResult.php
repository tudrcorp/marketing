<?php

namespace App\Services\Marketing;

class BirthdayNotificationTestSendResult
{
    /**
     * @param  list<BirthdayNotificationTestChannelResult>  $results
     */
    public function __construct(public array $results) {}

    public function allSuccessful(): bool
    {
        return collect($this->results)->every(
            fn (BirthdayNotificationTestChannelResult $result): bool => $result->successful,
        );
    }

    /**
     * @return list<BirthdayNotificationTestChannelResult>
     */
    public function failedResults(): array
    {
        return collect($this->results)
            ->filter(fn (BirthdayNotificationTestChannelResult $result): bool => ! $result->successful)
            ->values()
            ->all();
    }

    public function summary(): string
    {
        return collect($this->results)
            ->map(fn (BirthdayNotificationTestChannelResult $result): string => sprintf(
                '%s: %s',
                $result->channel->getLabel(),
                $result->successful ? 'enviado' : 'falló',
            ))
            ->join(' · ');
    }

    public function failureMessage(): ?string
    {
        $messages = collect($this->failedResults())
            ->map(fn (BirthdayNotificationTestChannelResult $result): string => sprintf(
                '%s — %s',
                $result->channel->getLabel(),
                $result->message ?? 'Error desconocido',
            ))
            ->all();

        if ($messages === []) {
            return null;
        }

        return implode(' ', $messages);
    }
}
