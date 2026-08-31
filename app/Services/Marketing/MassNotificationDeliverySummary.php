<?php

namespace App\Services\Marketing;

use App\Marketing\DispatchProgressStatus;
use App\Marketing\NotificationDispatchSource;
use App\Marketing\NotificationDispatchStatus;
use App\Models\MassNotification;
use App\Models\NotificationDispatchLog;
use Illuminate\Support\Collection;

class MassNotificationDeliverySummary
{
    public function __construct(
        private DispatchProgressTracker $progressTracker,
        private NotificationDispatchFailureInspector $failureInspector,
    ) {}

    /**
     * @return array{
     *     has_activity: bool,
     *     sent: int,
     *     failed: int,
     *     pending: int,
     *     total: int,
     *     ratio: float,
     *     mass_logs: list<array<string, mixed>>,
     *     test_logs: list<array<string, mixed>>,
     *     active_run: ?array<string, mixed>,
     * }
     */
    public function summarize(MassNotification $notification, ?int $userId = null): array
    {
        $testSources = [
            NotificationDispatchSource::MassTest->value,
            NotificationDispatchSource::BirthdayTest->value,
        ];

        /** @var Collection<int, NotificationDispatchLog> $massLogs */
        $massLogs = $notification->dispatchLogs()
            ->with('sentBy')
            ->whereNotIn('source', $testSources)
            ->limit(20)
            ->get();

        /** @var Collection<int, NotificationDispatchLog> $testLogs */
        $testLogs = $notification->dispatchLogs()
            ->with('sentBy')
            ->whereIn('source', $testSources)
            ->limit(20)
            ->get();

        // Los registros "en cola" son trabajo prometido, no entregas: cada lote genera después
        // su propio registro con el resultado real. Contarlos aquí inflaba el total (doble conteo)
        // y hacía que un envío encolado sin worker apareciera como "todo fallido".
        [$queuedLogs, $deliveryLogs] = $massLogs->partition(
            fn (NotificationDispatchLog $log): bool => $log->statusEnum() === NotificationDispatchStatus::Queued,
        );

        $sent = (int) $deliveryLogs->sum('sent_count');
        $total = (int) $deliveryLogs->sum('total_count');
        $failed = max(0, $total - $sent);
        $pending = $this->pendingRecipients($queuedLogs, $deliveryLogs);
        $ratio = $total > 0 ? round(($sent / $total) * 100, 1) : 0.0;

        $activeRun = null;

        if ($userId !== null) {
            $activeRun = $this->progressTracker->getActiveRunForNotification(
                $userId,
                (int) $notification->getKey(),
            );
        }

        if (is_array($activeRun) && ($activeRun['status'] ?? '') === DispatchProgressStatus::Processing->value) {
            $runSent = (int) ($activeRun['sent_recipients'] ?? 0);
            $runFailed = (int) ($activeRun['failed_recipients'] ?? 0);
            $runTotal = (int) ($activeRun['total_recipients'] ?? 0);

            $sent = max($sent, $runSent);
            $failed = max($failed, $runFailed);
            $total = max($total, $runTotal, $runSent + $runFailed);
            $pending = max(0, min($pending, $total - $sent - $failed));
            $ratio = (float) max($ratio, (int) ($activeRun['percent'] ?? 0));
        }

        return [
            'has_activity' => $massLogs->isNotEmpty() || $testLogs->isNotEmpty() || $activeRun !== null,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'total' => $total,
            'ratio' => $ratio,
            'mass_logs' => $massLogs->map(fn (NotificationDispatchLog $log): array => $this->mapLog($log))->all(),
            'test_logs' => $testLogs->map(fn (NotificationDispatchLog $log): array => $this->mapLog($log))->all(),
            'active_run' => $activeRun,
        ];
    }

    /**
     * Destinatarios encolados que todavía no tienen un resultado de entrega registrado.
     *
     * Solo cuenta la última tanda encolada: los lotes que ya se procesaron generan sus
     * propios registros con `logged_at` posterior, así que se descuentan de ese total.
     *
     * @param  Collection<int, NotificationDispatchLog>  $queuedLogs
     * @param  Collection<int, NotificationDispatchLog>  $deliveryLogs
     */
    private function pendingRecipients(Collection $queuedLogs, Collection $deliveryLogs): int
    {
        $lastQueued = $queuedLogs->sortByDesc('logged_at')->first();

        if ($lastQueued === null) {
            return 0;
        }

        $processedAfterQueue = (int) $deliveryLogs
            ->filter(fn (NotificationDispatchLog $log): bool => $log->logged_at !== null
                && $lastQueued->logged_at !== null
                && $log->logged_at->greaterThanOrEqualTo($lastQueued->logged_at))
            ->sum('total_count');

        return max(0, (int) $lastQueued->total_count - $processedAfterQueue);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLog(NotificationDispatchLog $log): array
    {
        $status = $log->statusEnum();
        $channel = $log->channelEnum();
        $source = $log->sourceEnum();
        $inspection = $status->isActionable() || ((int) $log->sent_count) < ((int) $log->total_count)
            ? $this->failureInspector->inspect($log)
            : ['failures' => []];

        return [
            'id' => $log->getKey(),
            'title' => $log->title,
            'summary' => $log->summary,
            'analyst_message' => $log->analyst_message,
            'status' => $status->value,
            'status_label' => $status->getLabel(),
            'status_color' => $status->getColor(),
            'channel_label' => $channel?->getLabel() ?? '—',
            'source_label' => $source->getLabel(),
            'sent' => (int) $log->sent_count,
            'total' => (int) $log->total_count,
            'ratio' => $log->deliveryRatio() ?? 0.0,
            'recipient' => $log->recipient,
            'sent_by' => $log->sentBy?->name,
            'logged_at' => $log->logged_at?->format('d/m/Y H:i'),
            'is_actionable' => $status->isActionable(),
            'is_test' => $source === NotificationDispatchSource::MassTest
                || $source === NotificationDispatchSource::BirthdayTest,
            'is_failed' => $status === NotificationDispatchStatus::Failed,
            'is_partial' => $status === NotificationDispatchStatus::Partial,
            'is_sent' => in_array($status, [NotificationDispatchStatus::Sent, NotificationDispatchStatus::Queued], true),
            'failures' => array_slice($inspection['failures'] ?? [], 0, 5),
            'failure_count' => count($inspection['failures'] ?? []),
        ];
    }
}
