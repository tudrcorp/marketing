<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\MassNotificationRecipient;
use App\Marketing\NotificationDispatchSource;
use App\Models\NotificationDispatchLog;
use App\Models\User;
use InvalidArgumentException;
use RuntimeException;

class NotificationDispatchRetryService
{
    public function __construct(
        private NotificationDispatchFailureInspector $inspector,
        private MassNotificationDispatchService $dispatchService,
    ) {}

    public function retry(NotificationDispatchLog $log, User $sentBy): MassNotificationChannelResult
    {
        $inspection = $this->inspector->inspect($log);

        if (! $inspection['can_retry']) {
            throw new InvalidArgumentException($inspection['retry_block_reason'] ?? 'No se puede reintentar este envío.');
        }

        $notification = $log->massNotification;

        if ($notification === null) {
            throw new RuntimeException('La campaña original ya no está disponible.');
        }

        $channel = $log->channelEnum();

        if ($channel === null) {
            throw new RuntimeException('El canal del envío original no es válido.');
        }

        $recipients = $this->recipientsFromFailures(
            failures: $inspection['failures'],
            channel: $channel,
            audience: $notification->audienceEnums()[0] ?? BirthdayNotificationAudience::Collaborators,
        );

        if ($recipients === []) {
            throw new InvalidArgumentException('No hay destinatarios válidos para reenviar.');
        }

        $source = NotificationDispatchSource::tryFrom((string) $log->source)
            ?? NotificationDispatchSource::MassIndividual;

        $results = $this->dispatchService->processChannels(
            notification: $this->notificationScopedToChannel($notification, $channel),
            recipients: $recipients,
            sentBy: $sentBy,
            source: $source,
            corporateEventId: $log->corporate_event_id,
        );

        $result = $results[0] ?? null;

        if ($result === null) {
            throw new RuntimeException('El reintento no produjo un resultado de canal.');
        }

        return $result;
    }

    /**
     * @param  list<array{address: string, reason: string, kind: string}>  $failures
     * @return list<MassNotificationRecipient>
     */
    private function recipientsFromFailures(
        array $failures,
        BirthdayNotificationChannel $channel,
        BirthdayNotificationAudience $audience,
    ): array {
        return collect($failures)
            ->unique('address')
            ->values()
            ->map(function (array $failure, int $index) use ($channel, $audience): MassNotificationRecipient {
                $address = $failure['address'];
                $isEmail = $channel === BirthdayNotificationChannel::Email
                    || ($failure['kind'] ?? '') === 'email';

                return new MassNotificationRecipient(
                    sourceId: 'retry-'.$index.'-'.md5($address),
                    name: $address,
                    audience: $audience,
                    email: $isEmail ? $address : null,
                    phone: $isEmail ? null : $address,
                );
            })
            ->all();
    }

    private function notificationScopedToChannel(
        \App\Models\MassNotification $notification,
        BirthdayNotificationChannel $channel,
    ): \App\Models\MassNotification {
        $scoped = $notification->replicate();
        $scoped->id = $notification->getKey();
        $scoped->exists = true;
        $scoped->channels = [$channel->value];

        return $scoped;
    }
}
