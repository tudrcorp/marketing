<?php

namespace App\Services\Marketing;

use App\Models\MassNotification;

readonly class MassNotificationDispatchResult
{
    /**
     * @param  list<MassNotificationChannelResult>  $channelResults
     */
    public function __construct(
        public MassNotification $notification,
        public array $channelResults,
        public ?string $dispatchRunId = null,
        public bool $queued = false,
    ) {}

    public function allSuccessful(): bool
    {
        if ($this->queued) {
            return true;
        }

        return collect($this->channelResults)->every(fn (MassNotificationChannelResult $result): bool => $result->successful);
    }

    public function summary(): string
    {
        if ($this->queued) {
            return 'El envío quedó en curso. Puedes seguir el avance en el panel de progreso.';
        }

        return collect($this->channelResults)
            ->map(fn (MassNotificationChannelResult $result): string => "{$result->channel->getLabel()}: {$result->message}")
            ->implode(' ');
    }

    public function failureMessage(): ?string
    {
        if ($this->queued) {
            return null;
        }

        $failures = collect($this->channelResults)
            ->reject(fn (MassNotificationChannelResult $result): bool => $result->successful)
            ->map(fn (MassNotificationChannelResult $result): string => "{$result->channel->getLabel()}: {$result->message}")
            ->values()
            ->all();

        if ($failures === []) {
            return null;
        }

        return implode(' ', $failures);
    }
}
