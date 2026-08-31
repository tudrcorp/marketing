<?php

namespace App\Jobs;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\MassNotificationRecipient;
use App\Marketing\NotificationDispatchSource;
use App\Models\MassNotification;
use App\Models\User;
use App\Services\Marketing\DispatchProgressTracker;
use App\Services\Marketing\MassNotificationDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessMassNotificationChannelsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    /**
     * @param  list<array{
     *     sourceId: string,
     *     name: string,
     *     audience: string,
     *     email: ?string,
     *     phone: ?string,
     *     replyPhone: ?string,
     *     replyContactName: ?string
     * }>  $recipients
     */
    public function __construct(
        public int $massNotificationId,
        public array $recipients,
        public int $sentById,
        public string $source,
        public ?string $dispatchRunId = null,
        public ?int $corporateEventId = null,
    ) {}

    public function handle(MassNotificationDispatchService $dispatchService): void
    {
        set_time_limit(0);

        $notification = MassNotification::query()->find($this->massNotificationId);
        $sentBy = User::query()->find($this->sentById);
        $source = NotificationDispatchSource::tryFrom($this->source);

        if ($notification === null || $sentBy === null || $source === null) {
            if ($this->dispatchRunId !== null) {
                app(DispatchProgressTracker::class)->markRunFailed(
                    $this->dispatchRunId,
                    'No se pudo continuar el envío: faltan datos de la campaña o del usuario.',
                );
            }

            return;
        }

        $dispatchService->processChannels(
            notification: $notification,
            recipients: $this->hydrateRecipients(),
            sentBy: $sentBy,
            source: $source,
            corporateEventId: $this->corporateEventId,
            dispatchRunId: $this->dispatchRunId,
        );
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->dispatchRunId === null) {
            return;
        }

        app(DispatchProgressTracker::class)->markRunFailed(
            $this->dispatchRunId,
            $exception?->getMessage() ?? 'El envío se interrumpió antes de completar el seguimiento.',
        );
    }

    /**
     * @return list<MassNotificationRecipient>
     */
    private function hydrateRecipients(): array
    {
        return collect($this->recipients)
            ->map(function (array $recipient): ?MassNotificationRecipient {
                $audience = BirthdayNotificationAudience::tryFrom((string) ($recipient['audience'] ?? ''));

                if ($audience === null || blank($recipient['sourceId'] ?? null)) {
                    return null;
                }

                return new MassNotificationRecipient(
                    sourceId: (string) $recipient['sourceId'],
                    name: (string) ($recipient['name'] ?? ''),
                    audience: $audience,
                    email: $recipient['email'] ?? null,
                    phone: $recipient['phone'] ?? null,
                    replyPhone: $recipient['replyPhone'] ?? null,
                    replyContactName: $recipient['replyContactName'] ?? null,
                );
            })
            ->filter()
            ->values()
            ->all();
    }
}
