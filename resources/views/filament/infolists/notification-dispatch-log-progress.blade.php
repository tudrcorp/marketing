@php
    use App\Services\Marketing\NotificationDispatchFailureInspector;

    /** @var \App\Models\NotificationDispatchLog $record */
    $inspection = app(NotificationDispatchFailureInspector::class)->inspect($record);
    $ratio = min(100, max(0, (float) $inspection['ratio']));
    $barClass = match (true) {
        $ratio >= 100.0 => 'marketing-dispatch-log-progress__bar-fill--complete',
        $ratio > 0 => 'marketing-dispatch-log-progress__bar-fill--partial',
        default => 'marketing-dispatch-log-progress__bar-fill--empty',
    };
@endphp

<section class="marketing-dispatch-log-progress" aria-label="Progreso y reintento del envío">
    <header class="marketing-dispatch-log-progress__header">
        <div>
            <p class="marketing-dispatch-log-progress__eyebrow">Entrega</p>
            <h3 class="marketing-dispatch-log-progress__title">Progreso del envío</h3>
            <p class="marketing-dispatch-log-progress__copy">
                Revisa cuántos destinatarios recibieron el mensaje y qué falló para decidir un reintento.
            </p>
        </div>

        <div class="marketing-dispatch-log-progress__metrics" aria-label="Métricas de entrega">
            <div class="marketing-dispatch-log-progress__metric">
                <span class="marketing-dispatch-log-progress__metric-value">{{ $inspection['sent'] }}</span>
                <span class="marketing-dispatch-log-progress__metric-label">Enviados</span>
            </div>
            <div class="marketing-dispatch-log-progress__metric">
                <span class="marketing-dispatch-log-progress__metric-value">{{ $inspection['failed'] }}</span>
                <span class="marketing-dispatch-log-progress__metric-label">Fallidos</span>
            </div>
            <div class="marketing-dispatch-log-progress__metric">
                <span class="marketing-dispatch-log-progress__metric-value">{{ $inspection['total'] }}</span>
                <span class="marketing-dispatch-log-progress__metric-label">Total</span>
            </div>
        </div>
    </header>

    <div class="marketing-dispatch-log-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $ratio }}" aria-label="Porcentaje aceptado por el servidor">
        <div @class(['marketing-dispatch-log-progress__bar-fill', $barClass]) style="width: {{ $ratio }}%"></div>
    </div>
    <p class="marketing-dispatch-log-progress__ratio">
        {{ $ratio }}% aceptado por el servidor · {{ $inspection['sent'] }} de {{ $inspection['total'] }}
        @if ($record->channelEnum() === \App\Marketing\BirthdayNotificationChannel::Email)
            <span class="marketing-dispatch-log-progress__ratio-note">· La aceptación SMTP no garantiza entrega al buzón</span>
        @endif
    </p>

    @if ($inspection['failures'] !== [])
        <div class="marketing-dispatch-log-progress__failures">
            <h4 class="marketing-dispatch-log-progress__failures-title">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-4" />
                Destinatarios con fallo
            </h4>
            <ul class="marketing-dispatch-log-progress__failure-list">
                @foreach ($inspection['failures'] as $failure)
                    <li class="marketing-dispatch-log-progress__failure-item">
                        <div class="marketing-dispatch-log-progress__failure-main">
                            <span class="marketing-dispatch-log-progress__failure-address">{{ $failure['address'] }}</span>
                            <span class="marketing-dispatch-log-progress__failure-kind">{{ $failure['kind'] === 'email' ? 'Correo' : 'Teléfono' }}</span>
                        </div>
                        <p class="marketing-dispatch-log-progress__failure-reason">
                            {{ $failure['analyst_message'] ?? $failure['reason'] }}
                        </p>
                        @if (filled($failure['resolution_steps'] ?? null))
                            <p class="marketing-dispatch-log-progress__failure-steps">
                                {{ $failure['resolution_steps'] }}
                            </p>
                        @endif
                        @if (filled($failure['reason'] ?? null) && ($failure['analyst_message'] ?? null) !== $failure['reason'])
                            <p class="marketing-dispatch-log-progress__failure-technical">
                                Detalle técnico: {{ $failure['reason'] }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="marketing-dispatch-log-progress__empty-failures">
            <x-filament::icon icon="heroicon-o-check-circle" class="size-5" />
            <p>No hay fallos individuales registrados para este envío.</p>
        </div>
    @endif

    <footer class="marketing-dispatch-log-progress__footer">
        @if ($inspection['can_retry'])
            <p class="marketing-dispatch-log-progress__hint">
                Usa <strong>Reintentar envío</strong> en la barra superior para volver a mandar solo a los destinatarios fallidos.
                Se creará un nuevo registro en el historial con el resultado del reintento.
            </p>
        @elseif (filled($inspection['retry_block_reason']))
            <p class="marketing-dispatch-log-progress__hint marketing-dispatch-log-progress__hint--muted">
                {{ $inspection['retry_block_reason'] }}
            </p>
        @endif
    </footer>
</section>
