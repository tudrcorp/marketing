@php
    use App\Filament\Resources\NotificationDispatchLogs\NotificationDispatchLogResource;
    use App\Services\Marketing\MassNotificationDeliverySummary;

    /** @var \App\Models\MassNotification $record */
    $summary = app(MassNotificationDeliverySummary::class)->summarize(
        $record,
        auth()->id() !== null ? (int) auth()->id() : null,
    );
    $ratio = min(100, max(0, (float) $summary['ratio']));
    $barClass = match (true) {
        $ratio >= 100.0 => 'mass-notification-delivery__bar-fill--complete',
        $ratio > 0 => 'mass-notification-delivery__bar-fill--partial',
        default => 'mass-notification-delivery__bar-fill--empty',
    };
    $activeRun = $summary['active_run'] ?? null;
    $isProcessing = is_array($activeRun) && ($activeRun['status'] ?? '') === 'processing';
@endphp

<section
    class="mass-notification-delivery"
    aria-label="Resumen del envío masivo"
    @if ($isProcessing)
        wire:poll.2s="refreshDeliverySummary"
    @endif
>
    @if ($isProcessing)
        <div
            class="mass-notification-delivery__active"
            role="status"
            x-data="{
                serverPercent: {{ (int) ($activeRun['percent'] ?? 0) }},
                sentCount: {{ (int) ($activeRun['sent_recipients'] ?? 0) }},
                totalRecipients: {{ (int) ($activeRun['total_recipients'] ?? 0) }},
                startedAt: {{ filled($activeRun['started_at'] ?? null) ? (int) (strtotime((string) $activeRun['started_at']) * 1000) : 'Date.now()' }},
                now: Date.now(),
                tick () { this.now = Date.now() },
                get displayPercent () {
                    if (this.sentCount > 0) {
                        return Math.max(5, Math.min(99, this.serverPercent))
                    }

                    const total = Math.max(1, this.totalRecipients)
                    const elapsedSeconds = Math.max(0, (this.now - this.startedAt) / 1000)
                    const expectedSeconds = Math.max(12, total * 1.25)
                    const estimated = Math.round((elapsedSeconds / expectedSeconds) * 100)

                    return Math.max(this.serverPercent, Math.min(95, Math.max(5, estimated)))
                },
            }"
            x-init="setInterval(() => tick(), 1000)"
        >
            <x-filament::icon icon="heroicon-o-arrow-path" class="size-5 animate-spin" />
            <div>
                <p class="mass-notification-delivery__active-title">Envío masivo en curso</p>
                <p class="mass-notification-delivery__active-copy">
                    {{ $activeRun['detail'] ?? 'Procesando…' }}
                    · <span x-text="displayPercent + '%'">{{ (int) ($activeRun['percent'] ?? 0) }}%</span>
                    · {{ (int) ($activeRun['sent_recipients'] ?? 0) }}/{{ (int) ($activeRun['total_recipients'] ?? 0) }} enviados
                    @if ((int) ($activeRun['failed_recipients'] ?? 0) > 0)
                        · {{ (int) $activeRun['failed_recipients'] }} fallido{{ (int) $activeRun['failed_recipients'] === 1 ? '' : 's' }}
                    @endif
                </p>
            </div>
        </div>
    @endif

    @if (! $summary['has_activity'])
        <div class="mass-notification-delivery__empty">
            <x-filament::icon icon="heroicon-o-paper-airplane" class="size-6" />
            <div>
                <p class="mass-notification-delivery__empty-title">Aún no hay envíos registrados</p>
                <p class="mass-notification-delivery__empty-copy">
                    Usa <strong>Envío de prueba</strong> para validar o <strong>Ejecutar envío masivo</strong> para disparar la campaña. El resultado aparecerá aquí.
                </p>
            </div>
        </div>
    @else
        <header class="mass-notification-delivery__header">
            <div>
                <p class="mass-notification-delivery__eyebrow">Resultados</p>
                <h3 class="mass-notification-delivery__title">Resumen del envío masivo</h3>
                <p class="mass-notification-delivery__copy">
                    Consolidado de entregas reales (sin contar pruebas). Abre cada registro para ver fallos y reintentar.
                </p>
            </div>
            <div class="mass-notification-delivery__metrics">
                <div class="mass-notification-delivery__metric">
                    <span class="mass-notification-delivery__metric-value">{{ $summary['sent'] }}</span>
                    <span class="mass-notification-delivery__metric-label">Enviados</span>
                </div>
                <div class="mass-notification-delivery__metric">
                    <span class="mass-notification-delivery__metric-value">{{ $summary['failed'] }}</span>
                    <span class="mass-notification-delivery__metric-label">Fallidos</span>
                </div>
                @if (($summary['pending'] ?? 0) > 0)
                    <div class="mass-notification-delivery__metric">
                        <span class="mass-notification-delivery__metric-value">{{ $summary['pending'] }}</span>
                        <span class="mass-notification-delivery__metric-label">En cola</span>
                    </div>
                @endif
                <div class="mass-notification-delivery__metric">
                    <span class="mass-notification-delivery__metric-value">{{ $summary['total'] }}</span>
                    <span class="mass-notification-delivery__metric-label">Total</span>
                </div>
            </div>
        </header>

        <div class="mass-notification-delivery__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $ratio }}">
            <div @class(['mass-notification-delivery__bar-fill', $barClass]) style="width: {{ $ratio }}%"></div>
        </div>
        <p class="mass-notification-delivery__ratio">
            @if ($summary['total'] > 0)
                {{ $ratio }}% aceptado por el servidor · {{ $summary['sent'] }} de {{ $summary['total'] }}
            @else
                Sin entregas procesadas todavía
            @endif
            @if (($summary['pending'] ?? 0) > 0)
                <span class="mass-notification-delivery__ratio-note">
                    · {{ $summary['pending'] }} destinatario{{ $summary['pending'] === 1 ? '' : 's' }} en cola, a la espera del worker
                </span>
            @endif
            <span class="mass-notification-delivery__ratio-note">· En correo, un rebote 550 puede llegar después</span>
        </p>

        @if ($summary['mass_logs'] !== [])
            <div class="mass-notification-delivery__list">
                <h4 class="mass-notification-delivery__list-title">Envíos masivos</h4>
                <ul class="mass-notification-delivery__items">
                    @foreach ($summary['mass_logs'] as $log)
                        <li @class([
                            'mass-notification-delivery__item',
                            'mass-notification-delivery__item--failed' => $log['is_failed'],
                            'mass-notification-delivery__item--partial' => $log['is_partial'],
                            'mass-notification-delivery__item--sent' => $log['is_sent'],
                        ])>
                            <div class="mass-notification-delivery__item-main">
                                <div>
                                    <p class="mass-notification-delivery__item-channel">{{ $log['channel_label'] }} · {{ $log['source_label'] }}</p>
                                    <p class="mass-notification-delivery__item-summary">{{ $log['analyst_message'] ?: $log['summary'] }}</p>
                                    <p class="mass-notification-delivery__item-meta">
                                        {{ $log['sent'] }} de {{ $log['total'] }}
                                        @if ($log['logged_at']) · {{ $log['logged_at'] }} @endif
                                        @if ($log['sent_by']) · {{ $log['sent_by'] }} @endif
                                    </p>
                                    @if (($log['failures'] ?? []) !== [])
                                        <ul class="mass-notification-delivery__failures">
                                            @foreach ($log['failures'] as $failure)
                                                <li>
                                                    <strong>{{ $failure['address'] }}</strong>
                                                    — {{ $failure['analyst_message'] ?? $failure['reason'] }}
                                                </li>
                                            @endforeach
                                            @if (($log['failure_count'] ?? 0) > count($log['failures']))
                                                <li>+{{ $log['failure_count'] - count($log['failures']) }} más en el detalle</li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                                <div class="mass-notification-delivery__item-aside">
                                    <span class="mass-notification-delivery__status mass-notification-delivery__status--{{ $log['status_color'] }}">
                                        {{ $log['status_label'] }}
                                    </span>
                                    <a
                                        href="{{ NotificationDispatchLogResource::getUrl('view', ['record' => $log['id']], panel: 'marketing') }}"
                                        class="mass-notification-delivery__link"
                                    >
                                        Ver detalle
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @elseif (! $isProcessing)
            <div class="mass-notification-delivery__list">
                <h4 class="mass-notification-delivery__list-title">Envíos masivos</h4>
                <p class="mass-notification-delivery__empty-copy">
                    Todavía no hay resultados de envío masivo. Cuando ejecutes la campaña, aparecerán aquí junto a las pruebas.
                </p>
            </div>
        @endif

        @if ($summary['test_logs'] !== [])
            <div class="mass-notification-delivery__list">
                <h4 class="mass-notification-delivery__list-title">Pruebas</h4>
                <ul class="mass-notification-delivery__items">
                    @foreach ($summary['test_logs'] as $log)
                        <li class="mass-notification-delivery__item">
                            <div class="mass-notification-delivery__item-main">
                                <div>
                                    <p class="mass-notification-delivery__item-channel">{{ $log['channel_label'] }}</p>
                                    <p class="mass-notification-delivery__item-summary">{{ $log['summary'] }}</p>
                                    <p class="mass-notification-delivery__item-meta">
                                        @if ($log['recipient']) {{ $log['recipient'] }} · @endif
                                        {{ $log['logged_at'] }}
                                    </p>
                                </div>
                                <div class="mass-notification-delivery__item-aside">
                                    <span class="mass-notification-delivery__status mass-notification-delivery__status--{{ $log['status_color'] }}">
                                        {{ $log['status_label'] }}
                                    </span>
                                    <a
                                        href="{{ NotificationDispatchLogResource::getUrl('view', ['record' => $log['id']], panel: 'marketing') }}"
                                        class="mass-notification-delivery__link"
                                    >
                                        Ver detalle
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</section>
