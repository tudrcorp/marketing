<?php

namespace App\Services\Marketing;

use App\Marketing\DispatchProgressStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DispatchProgressTracker
{
    private const int TtlSeconds = 86_400;

    private const int CompletedDisplaySeconds = 45;

    private const int StaleProcessingSeconds = 300;

    public function start(int $userId, int $massNotificationId, string $title, int $totalRecipients): string
    {
        $runId = (string) Str::uuid();
        $now = now()->toIso8601String();

        $run = [
            'id' => $runId,
            'user_id' => $userId,
            'mass_notification_id' => $massNotificationId,
            'title' => $title,
            'total_recipients' => $totalRecipients,
            'sent_recipients' => 0,
            'failed_recipients' => 0,
            'total_units' => 0,
            'completed_units' => 0,
            'status' => DispatchProgressStatus::Processing->value,
            'current_channel' => null,
            'detail' => 'Iniciando envío…',
            'percent' => 5,
            'failures' => [],
            'started_at' => $now,
            'updated_at' => $now,
            'finished_at' => null,
            'dismissed' => false,
        ];

        Cache::put($this->runKey($runId), $run, self::TtlSeconds);
        $this->rememberUserRun($userId, $runId);

        DispatchProgressBroadcaster::started();

        return $runId;
    }

    public function markJobQueued(string $runId, string $detail): void
    {
        $this->mutate($runId, function (array $run) use ($detail): array {
            $run['detail'] = $detail;
            $run['percent'] = max((int) ($run['percent'] ?? 0), 5);

            return $run;
        });
    }

    public function markBatchProcessing(
        string $runId,
        string $channelLabel,
        int $batchNumber,
        int $totalBatches,
        int $recipientCount,
    ): void {
        $this->mutate($runId, function (array $run) use ($channelLabel, $batchNumber, $totalBatches, $recipientCount): array {
            if ((int) ($run['total_units'] ?? 0) === 0) {
                $run['total_units'] = $totalBatches;
            }

            $run['current_channel'] = $channelLabel;
            $run['detail'] = "{$channelLabel} · Procesando lote {$batchNumber}/{$totalBatches} ({$recipientCount} destinatario".($recipientCount === 1 ? '' : 's').')…';
            $run['percent'] = max($this->calculatePercent($run), 5);

            return $run;
        });

        DispatchProgressBroadcaster::started();
    }

    public function registerChannelUnits(string $runId, string $channelLabel, int $units, string $detail): void
    {
        if ($units <= 0) {
            return;
        }

        $this->mutate($runId, function (array $run) use ($channelLabel, $units, $detail): array {
            $run['total_units'] += $units;
            $run['current_channel'] = $channelLabel;
            $run['detail'] = $detail;
            $run['percent'] = max($this->calculatePercent($run), 5);

            return $run;
        });

        DispatchProgressBroadcaster::started();
    }

    public function markRunFailed(string $runId, string $technicalDetail): void
    {
        $this->mutate($runId, function (array $run) use ($technicalDetail): array {
            if (($run['status'] ?? '') !== DispatchProgressStatus::Processing->value) {
                return $run;
            }

            $presented = $this->presentError((string) ($run['current_channel'] ?? 'Envío'), $technicalDetail);

            $run['status'] = DispatchProgressStatus::Failed->value;
            $run['percent'] = 100;
            $run['detail'] = $presented['summary'];
            $run['last_error_summary'] = $presented['summary'];
            $run['log_hint'] = $presented['log_hint'];
            $run['show_log_hint'] = true;
            $run['finished_at'] = now()->toIso8601String();

            return $run;
        });

        DispatchProgressBroadcaster::started();
    }

    public function markBatchError(
        string $runId,
        string $channelLabel,
        string $technicalDetail,
        ?int $batchNumber = null,
        ?int $totalBatches = null,
    ): void {
        $this->mutate($runId, function (array $run) use ($channelLabel, $technicalDetail, $batchNumber, $totalBatches): array {
            $presented = $this->presentError($channelLabel, $technicalDetail);

            $run['current_channel'] = $channelLabel;
            $run['detail'] = $this->formatDetail($presented['summary'], $channelLabel, $batchNumber, $totalBatches);
            $run['last_error_summary'] = $presented['summary'];
            $run['log_hint'] = $presented['log_hint'];
            $run['show_log_hint'] = true;

            return $run;
        });

        DispatchProgressBroadcaster::started();
    }

    /**
     * Contabiliza los correos que sí salieron en un intento parcial sin dar el lote por
     * terminado: el lote se reprograma con los pendientes y cerrará su unidad al completarse.
     */
    public function recordPartialProgress(
        string $runId,
        string $channelLabel,
        int $sent,
        string $detail,
        ?int $batchNumber = null,
        ?int $totalBatches = null,
    ): void {
        $this->mutate($runId, function (array $run) use ($channelLabel, $sent, $detail, $batchNumber, $totalBatches): array {
            $run['current_channel'] = $channelLabel;
            $run['sent_recipients'] += max(0, $sent);
            $run['detail'] = $this->formatDetail($detail, $channelLabel, $batchNumber, $totalBatches);
            $run['percent'] = max($this->calculatePercent($run), 5);

            return $run;
        });

        DispatchProgressBroadcaster::started();
    }

    /**
     * @param  list<array{address?: string, reason?: string, analyst_message?: string}>  $recipientFailures
     */
    public function recordUnitCompletion(
        string $runId,
        string $channelLabel,
        int $sent,
        int $failed,
        string $detail,
        ?int $batchNumber = null,
        ?int $totalBatches = null,
        ?string $unitKey = null,
        array $recipientFailures = [],
    ): void {
        $this->mutate($runId, function (array $run) use ($channelLabel, $sent, $failed, $detail, $batchNumber, $totalBatches, $unitKey, $recipientFailures): array {
            $resolvedUnitKey = $unitKey
                ?? ($batchNumber !== null ? "batch-{$batchNumber}" : 'default');
            $recordedUnitKeys = is_array($run['recorded_unit_keys'] ?? null) ? $run['recorded_unit_keys'] : [];

            if (in_array($resolvedUnitKey, $recordedUnitKeys, true)
                || ($batchNumber !== null && in_array($batchNumber, $run['recorded_batches'] ?? [], true))) {
                if ($failed > 0) {
                    $presented = $this->presentError($channelLabel, $detail);
                    $run['detail'] = $this->formatDetail($presented['summary'], $channelLabel, $batchNumber, $totalBatches);
                    $run['last_error_summary'] = $presented['summary'];
                    $run['log_hint'] = $presented['log_hint'];
                    $run['show_log_hint'] = true;
                    $run = $this->mergeRecipientFailures($run, $channelLabel, $failed, $presented['summary'], $detail, $batchNumber, $recipientFailures);
                }

                return $run;
            }

            if ((int) ($run['total_units'] ?? 0) === 0) {
                $run['total_units'] = 1;
            }

            $recordedUnitKeys[] = $resolvedUnitKey;
            $run['recorded_unit_keys'] = $recordedUnitKeys;

            if ($batchNumber !== null) {
                $recordedBatches = is_array($run['recorded_batches'] ?? null) ? $run['recorded_batches'] : [];
                $recordedBatches[] = $batchNumber;
                $run['recorded_batches'] = $recordedBatches;
            }

            $run['completed_units'] = min($run['total_units'], $run['completed_units'] + 1);
            $run['sent_recipients'] += $sent;
            $run['failed_recipients'] += $failed;
            $run['current_channel'] = $channelLabel;

            if ($failed > 0) {
                $presented = $this->presentError($channelLabel, $detail);
                $run['detail'] = $this->formatDetail($presented['summary'], $channelLabel, $batchNumber, $totalBatches);
                $run['last_error_summary'] = $presented['summary'];
                $run['log_hint'] = $presented['log_hint'];
                $run['show_log_hint'] = true;
                $run = $this->mergeRecipientFailures($run, $channelLabel, $failed, $presented['summary'], $detail, $batchNumber, $recipientFailures);
            } else {
                $run['detail'] = $this->formatDetail($detail, $channelLabel, $batchNumber, $totalBatches);
            }

            $run['percent'] = $this->calculatePercent($run);

            if ($run['completed_units'] >= $run['total_units']) {
                $run['status'] = $this->resolveTerminalStatus($run)->value;
                $run['detail'] = $this->resolveTerminalDetail($run);
                $run['finished_at'] = now()->toIso8601String();
                $run['percent'] = 100;
            }

            return $run;
        });

        DispatchProgressBroadcaster::started();
    }

    public function hasActiveRunsForNotification(int $userId, int $massNotificationId): bool
    {
        foreach ($this->getRunsForUser($userId) as $run) {
            if ((int) ($run['mass_notification_id'] ?? 0) !== $massNotificationId) {
                continue;
            }

            if (($run['status'] ?? '') === DispatchProgressStatus::Processing->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getActiveRunForNotification(int $userId, int $massNotificationId): ?array
    {
        foreach ($this->getRunsForUser($userId) as $run) {
            if ((int) ($run['mass_notification_id'] ?? 0) !== $massNotificationId) {
                continue;
            }

            return $run;
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRunsForUser(int $userId): array
    {
        $runIds = Cache::get($this->userRunsKey($userId), []);

        if ($runIds === []) {
            return [];
        }

        $runs = [];

        foreach ($runIds as $runId) {
            $run = Cache::get($this->runKey($runId));

            if (! is_array($run) || ($run['dismissed'] ?? false)) {
                continue;
            }

            $run = $this->reconcileStaleProcessingRun($runId, $run);

            if ($this->shouldHideCompletedRun($run)) {
                continue;
            }

            $runs[] = $run;
        }

        usort($runs, fn (array $left, array $right): int => strcmp(
            (string) ($right['started_at'] ?? ''),
            (string) ($left['started_at'] ?? ''),
        ));

        return $runs;
    }

    public function dismiss(string $runId, int $userId): void
    {
        $run = Cache::get($this->runKey($runId));

        if (! is_array($run) || (int) ($run['user_id'] ?? 0) !== (int) $userId) {
            return;
        }

        $run['dismissed'] = true;
        $run['updated_at'] = now()->toIso8601String();

        Cache::put($this->runKey($runId), $run, self::TtlSeconds);
    }

    public function hasActiveRuns(int $userId): bool
    {
        foreach ($this->getRunsForUser($userId) as $run) {
            if (($run['status'] ?? '') === DispatchProgressStatus::Processing->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    private function mutate(string $runId, callable $callback): void
    {
        $lock = Cache::lock($this->lockKey($runId), 5);

        $lock->block(3, function () use ($runId, $callback): void {
            $run = Cache::get($this->runKey($runId));

            if (! is_array($run)) {
                return;
            }

            $run = $callback($run);
            $run['updated_at'] = now()->toIso8601String();

            Cache::put($this->runKey($runId), $run, self::TtlSeconds);
        });
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function calculatePercent(array $run): int
    {
        $isProcessing = ($run['status'] ?? '') === DispatchProgressStatus::Processing->value;
        $totalRecipients = (int) ($run['total_recipients'] ?? 0);
        $processedRecipients = (int) ($run['sent_recipients'] ?? 0) + (int) ($run['failed_recipients'] ?? 0);

        if ($totalRecipients > 0 && $processedRecipients > 0) {
            $percent = (int) round(($processedRecipients / $totalRecipients) * 100);
        } else {
            $totalUnits = (int) ($run['total_units'] ?? 0);

            if ($totalUnits === 0) {
                return $isProcessing ? 5 : 0;
            }

            $percent = (int) round(((int) $run['completed_units'] / $totalUnits) * 100);
        }

        if ($isProcessing) {
            return (int) min(99, max(5, $percent));
        }

        return (int) min(100, $percent);
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function resolveTerminalDetail(array $run): string
    {
        $failed = (int) ($run['failed_recipients'] ?? 0);

        if ($failed > 0 && filled($run['last_error_summary'] ?? null)) {
            return (string) $run['last_error_summary'];
        }

        return $this->buildTerminalDetail($run);
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function buildTerminalDetail(array $run): string
    {
        $failed = (int) ($run['failed_recipients'] ?? 0);
        $sent = (int) ($run['sent_recipients'] ?? 0);

        if ($failed === 0) {
            return "Envío completado: {$sent} destinatario".($sent === 1 ? '' : 's').' notificado'.($sent === 1 ? '' : 's').'.';
        }

        if ($sent === 0) {
            return "Envío fallido: {$failed} destinatario".($failed === 1 ? '' : 's').' sin entregar.';
        }

        return "Envío finalizado: {$sent} exitoso".($sent === 1 ? '' : 's').", {$failed} fallido".($failed === 1 ? '' : 's').'.';
    }

    /**
     * @return array{summary: string, log_hint: string}
     */
    private function presentError(string $channelLabel, string $technicalDetail): array
    {
        return app(DispatchProgressErrorPresenter::class)->presentForChannelLabel($channelLabel, $technicalDetail);
    }

    /**
     * @param  array<string, mixed>  $run
     * @param  list<array{address?: string, reason?: string, analyst_message?: string}>  $recipientFailures
     * @return array<string, mixed>
     */
    private function mergeRecipientFailures(
        array $run,
        string $channelLabel,
        int $failed,
        string $summary,
        string $technicalDetail,
        ?int $batchNumber,
        array $recipientFailures,
    ): array {
        $failures = is_array($run['failures'] ?? null) ? $run['failures'] : [];
        $normalizedRecipients = collect($recipientFailures)
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['address'] ?? null))
            ->map(fn (array $row): array => [
                'address' => (string) $row['address'],
                'reason' => (string) ($row['reason'] ?? $technicalDetail),
                'analyst_message' => (string) ($row['analyst_message'] ?? $summary),
            ])
            ->values()
            ->all();

        $failures[] = [
            'channel' => $channelLabel,
            'count' => $failed,
            'detail' => $summary,
            'technical_detail' => $technicalDetail,
            'batch_number' => $batchNumber,
            'recipients' => $normalizedRecipients,
        ];
        $run['failures'] = array_slice($failures, -10);

        return $run;
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function resolveTerminalStatus(array $run): DispatchProgressStatus
    {
        $failed = (int) ($run['failed_recipients'] ?? 0);
        $sent = (int) ($run['sent_recipients'] ?? 0);

        if ($failed > 0 && $sent === 0) {
            return DispatchProgressStatus::Failed;
        }

        if ($failed > 0) {
            return DispatchProgressStatus::Partial;
        }

        return DispatchProgressStatus::Completed;
    }

    private function formatDetail(
        string $detail,
        string $channelLabel,
        ?int $batchNumber,
        ?int $totalBatches,
    ): string {
        if ($batchNumber !== null && $totalBatches !== null && $totalBatches > 1) {
            return "{$channelLabel} · Lote {$batchNumber}/{$totalBatches} · {$detail}";
        }

        return "{$channelLabel} · {$detail}";
    }

    /**
     * @param  array<string, mixed>  $run
     */
    private function shouldHideCompletedRun(array $run): bool
    {
        if (($run['status'] ?? '') === DispatchProgressStatus::Processing->value) {
            return false;
        }

        $finishedAt = $run['finished_at'] ?? null;

        if (! is_string($finishedAt) || $finishedAt === '') {
            return false;
        }

        return now()->diffInSeconds(Carbon::parse($finishedAt)) > self::CompletedDisplaySeconds;
    }

    /**
     * @param  array<string, mixed>  $run
     * @return array<string, mixed>
     */
    private function reconcileStaleProcessingRun(string $runId, array $run): array
    {
        if (($run['status'] ?? '') !== DispatchProgressStatus::Processing->value) {
            return $run;
        }

        $startedAt = $run['started_at'] ?? null;

        if (! is_string($startedAt) || $startedAt === '') {
            return $run;
        }

        if (now()->diffInSeconds(Carbon::parse($startedAt)) < self::StaleProcessingSeconds) {
            return $run;
        }

        $this->mutate($runId, function (array $current): array {
            if (($current['status'] ?? '') !== DispatchProgressStatus::Processing->value) {
                return $current;
            }

            $current['status'] = DispatchProgressStatus::Failed->value;
            $current['percent'] = 100;
            $current['detail'] = 'El seguimiento del envío quedó incompleto. Revisa el Historial de envíos para confirmar el resultado real.';
            $current['last_error_summary'] = $current['detail'];
            $current['log_hint'] = DispatchProgressErrorPresenter::LogHint;
            $current['show_log_hint'] = true;
            $current['finished_at'] = now()->toIso8601String();

            return $current;
        });

        $updated = Cache::get($this->runKey($runId));

        return is_array($updated) ? $updated : $run;
    }

    private function rememberUserRun(int $userId, string $runId): void
    {
        $key = $this->userRunsKey($userId);
        $runIds = Cache::get($key, []);

        if (! in_array($runId, $runIds, true)) {
            $runIds[] = $runId;
        }

        $runIds = array_slice($runIds, -20);

        Cache::put($key, $runIds, self::TtlSeconds);
    }

    private function runKey(string $runId): string
    {
        return "marketing.dispatch-progress.run.{$runId}";
    }

    private function userRunsKey(int $userId): string
    {
        return "marketing.dispatch-progress.user.{$userId}";
    }

    private function lockKey(string $runId): string
    {
        return "marketing.dispatch-progress.lock.{$runId}";
    }
}
