<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchStatus;

class DispatchProgressErrorPresenter
{
    public const string LogHint = 'Consulta el Historial de envíos para ver el detalle técnico del error.';

    public function __construct(
        private NotificationDispatchFailureResolver $failureResolver,
    ) {}

    /**
     * @return array{summary: string, log_hint: string}
     */
    public function present(?BirthdayNotificationChannel $channel, string $technicalMessage): array
    {
        $guidance = $this->failureResolver->resolve(
            NotificationDispatchStatus::Failed,
            $technicalMessage,
            $channel,
        );

        return [
            'summary' => $this->truncate($guidance['analyst_message']),
            'log_hint' => self::LogHint,
        ];
    }

    /**
     * @return array{summary: string, log_hint: string}
     */
    public function presentForChannelLabel(?string $channelLabel, string $technicalMessage): array
    {
        return $this->present($this->resolveChannel($channelLabel), $technicalMessage);
    }

    private function resolveChannel(?string $channelLabel): ?BirthdayNotificationChannel
    {
        if ($channelLabel === null) {
            return null;
        }

        foreach (BirthdayNotificationChannel::cases() as $channel) {
            if ($channel->getLabel() === $channelLabel) {
                return $channel;
            }
        }

        return null;
    }

    private function truncate(string $message): string
    {
        if (mb_strlen($message) <= 160) {
            return $message;
        }

        return mb_substr($message, 0, 157).'…';
    }
}
