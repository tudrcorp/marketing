<?php

namespace App\Services\Marketing;

use App\Marketing\BirthdayNotificationChannel;
use App\Marketing\NotificationDispatchStatus;
use App\Models\NotificationDispatchLog;

class NotificationDispatchFailureInspector
{
    public function __construct(
        private NotificationDispatchFailureResolver $failureResolver,
    ) {}

    /**
     * @return array{
     *     sent: int,
     *     total: int,
     *     failed: int,
     *     ratio: float,
     *     failures: list<array{address: string, reason: string, kind: string, analyst_message: string, resolution_steps: string, failure_code: string}>,
     *     can_retry: bool,
     *     retry_block_reason: ?string,
     * }
     */
    public function inspect(NotificationDispatchLog $log): array
    {
        $failures = $this->extractFailures($log);
        $total = max(0, (int) $log->total_count);
        $sent = max(0, (int) $log->sent_count);

        if ($total === 0 && $failures !== []) {
            $total = count($failures);
        }

        $failed = max($total - $sent, count($failures));
        $ratio = $total > 0 ? round(($sent / $total) * 100, 1) : 0.0;

        [$canRetry, $blockReason] = $this->canRetry($log, $failures);

        return [
            'sent' => $sent,
            'total' => $total,
            'failed' => $failed,
            'ratio' => $ratio,
            'failures' => $failures,
            'can_retry' => $canRetry,
            'retry_block_reason' => $blockReason,
        ];
    }

    /**
     * @return list<array{address: string, reason: string, kind: string, analyst_message: string, resolution_steps: string, failure_code: string}>
     */
    public function extractFailures(NotificationDispatchLog $log): array
    {
        $detail = is_array($log->technical_detail) ? $log->technical_detail : [];
        $apiCalls = is_array($detail['api_calls'] ?? null) ? $detail['api_calls'] : [];
        $channel = $log->channelEnum() ?? BirthdayNotificationChannel::Email;
        $kind = $channel === BirthdayNotificationChannel::Email ? 'email' : 'phone';
        $defaultReason = filled($log->summary) ? (string) $log->summary : 'Envío fallido';

        foreach ($apiCalls as $call) {
            if (! is_array($call)) {
                continue;
            }

            $body = $call['response']['body'] ?? null;

            if (is_array($body) && is_array($body['failures'] ?? null) && $body['failures'] !== []) {
                return $this->normalizeFailureRows($body['failures'], $kind, $defaultReason, $channel);
            }
        }

        foreach ($apiCalls as $call) {
            if (! is_array($call)) {
                continue;
            }

            $recipients = $call['request']['recipients'] ?? null;

            if (! is_array($recipients) || $recipients === []) {
                continue;
            }

            $responseSuccessful = (bool) ($call['response']['successful'] ?? false);
            $shouldTreatAsFailed = ! $responseSuccessful
                || $log->statusEnum()->isActionable()
                || ((int) $log->sent_count) < ((int) $log->total_count);

            if (! $shouldTreatAsFailed) {
                continue;
            }

            $reason = $defaultReason;

            if (filled($call['error']['message'] ?? null)) {
                $reason = (string) $call['error']['message'];
            } elseif (is_array($call['response']['body'] ?? null)) {
                $body = $call['response']['body'];
                foreach (['reason', 'error', 'message'] as $key) {
                    if (filled($body[$key] ?? null)) {
                        $reason = (string) $body[$key];
                        break;
                    }
                }
            }

            return collect($recipients)
                ->filter(fn (mixed $address): bool => filled($address) && is_string($address))
                ->map(fn (string $address): array => $this->presentFailure($address, $reason, $kind, $channel))
                ->values()
                ->all();
        }

        if (filled($log->recipient) && $log->statusEnum()->isActionable()) {
            return [$this->presentFailure((string) $log->recipient, $defaultReason, $kind, $channel)];
        }

        return [];
    }

    /**
     * @param  list<array{address: string, reason: string, kind: string}>  $failures
     * @return array{0: bool, 1: ?string}
     */
    private function canRetry(NotificationDispatchLog $log, array $failures): array
    {
        if (! $log->statusEnum()->isActionable()) {
            return [false, 'Este envío no requiere reintento.'];
        }

        if ($log->failure_code === 'api_email_timeout_unconfirmed') {
            return [false, 'El API no confirmó el resultado a tiempo (timeout), pero es probable que los correos ya se hayan enviado. Verifica manualmente antes de reintentar para evitar duplicados.'];
        }

        if ($log->mass_notification_id === null) {
            return [false, 'Solo se pueden reintentar envíos vinculados a una notificación masiva.'];
        }

        if ($log->massNotification === null) {
            return [false, 'La campaña original ya no está disponible.'];
        }

        $channel = $log->channelEnum();

        if ($channel === null || $channel === BirthdayNotificationChannel::Sms) {
            return [false, 'Este canal no admite reintento desde el historial.'];
        }

        if ($failures === []) {
            return [false, 'No hay destinatarios fallidos identificables para reenviar.'];
        }

        return [true, null];
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<array{address: string, reason: string, kind: string, analyst_message: string, resolution_steps: string, failure_code: string}>
     */
    private function normalizeFailureRows(
        array $rows,
        string $kind,
        string $defaultReason,
        BirthdayNotificationChannel $channel,
    ): array {
        return collect($rows)
            ->map(function (mixed $row) use ($kind, $defaultReason, $channel): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $address = $row['email'] ?? $row['phone'] ?? $row['to'] ?? $row['address'] ?? null;

                if (! filled($address) || ! is_string($address)) {
                    return null;
                }

                $rowKind = filled($row['email'] ?? null) ? 'email' : (filled($row['phone'] ?? null) ? 'phone' : $kind);
                $reason = filled($row['reason'] ?? null) ? (string) $row['reason'] : $defaultReason;

                return $this->presentFailure($address, $reason, $rowKind, $channel);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{address: string, reason: string, kind: string, analyst_message: string, resolution_steps: string, failure_code: string}
     */
    private function presentFailure(
        string $address,
        string $reason,
        string $kind,
        BirthdayNotificationChannel $channel,
    ): array {
        $guidance = $this->failureResolver->resolve(
            status: NotificationDispatchStatus::Failed,
            technicalMessage: $reason,
            channel: $channel,
        );

        return [
            'address' => $address,
            'reason' => $reason,
            'kind' => $kind,
            'analyst_message' => $guidance['analyst_message'],
            'resolution_steps' => $guidance['resolution_steps'],
            'failure_code' => $guidance['failure_code'],
        ];
    }
}
