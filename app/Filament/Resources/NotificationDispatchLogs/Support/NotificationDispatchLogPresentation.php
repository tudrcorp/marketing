<?php

namespace App\Filament\Resources\NotificationDispatchLogs\Support;

use App\Marketing\NotificationDispatchStatus;
use App\Models\NotificationDispatchLog;
use Illuminate\Support\Facades\DB;

class NotificationDispatchLogPresentation
{
    /**
     * @return array{
     *     total: int,
     *     failures: int,
     *     partial: int,
     *     sent: int,
     *     attention: int,
     * }
     */
    public static function todayStats(): array
    {
        $rows = NotificationDispatchLog::query()
            ->select('status', DB::raw('count(*) as total'))
            ->whereDate('logged_at', today())
            ->groupBy('status')
            ->pluck('total', 'status');

        $failures = (int) ($rows[NotificationDispatchStatus::Failed->value] ?? 0);
        $partial = (int) ($rows[NotificationDispatchStatus::Partial->value] ?? 0);
        $skipped = (int) ($rows[NotificationDispatchStatus::Skipped->value] ?? 0);
        $sent = (int) ($rows[NotificationDispatchStatus::Sent->value] ?? 0)
            + (int) ($rows[NotificationDispatchStatus::Queued->value] ?? 0);

        return [
            'total' => $rows->sum(),
            'failures' => $failures,
            'partial' => $partial,
            'sent' => $sent,
            'attention' => $failures + $partial + $skipped,
        ];
    }

    public static function statusGlassClass(NotificationDispatchLog $record): string
    {
        return match ($record->statusEnum()) {
            NotificationDispatchStatus::Sent, NotificationDispatchStatus::Queued => 'marketing-dispatch-log-glass--healthy',
            NotificationDispatchStatus::Partial => 'marketing-dispatch-log-glass--warning',
            NotificationDispatchStatus::Failed => 'marketing-dispatch-log-glass--unhealthy',
            NotificationDispatchStatus::Skipped => 'marketing-dispatch-log-glass--muted',
        };
    }

    public static function batchLabel(NotificationDispatchLog $record): ?string
    {
        if ($record->batch_number === null || $record->total_batches === null) {
            return null;
        }

        return "Lote {$record->batch_number} de {$record->total_batches}";
    }
}
