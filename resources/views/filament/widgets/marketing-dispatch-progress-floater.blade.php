@php
    use App\Filament\Resources\NotificationDispatchLogs\NotificationDispatchLogResource;
    use App\Marketing\DispatchProgressStatus;
    use App\Services\Marketing\DispatchProgressErrorPresenter;

    $hasRuns = $runs !== [];
    $positionStorageKey = 'marketing.dispatch-progress.position.'.(auth()->id() ?? 'guest');
    $dispatchLogUrl = NotificationDispatchLogResource::getUrl('index', panel: 'marketing');
@endphp

<div
    wire:ignore.self
    data-dispatch-progress-floater
    data-position-key="{{ $positionStorageKey }}"
    class="marketing-dispatch-progress-floater-shell"
>
    <div
        @class([
            'marketing-dispatch-progress-floater',
            'marketing-dispatch-progress-floater--visible' => $hasRuns,
        ])
        @if ($hasRuns)
            wire:poll.{{ $shouldPoll ? '500ms' : '3s' }}="refreshRuns"
        @endif
        aria-live="polite"
        aria-label="Progreso de envíos en curso"
    >
        @if ($hasRuns)
            <div
                class="marketing-dispatch-glass marketing-dispatch-progress-floater__handle"
                data-dispatch-progress-handle
                title="Arrastra para mover"
            >
                <span class="marketing-dispatch-glass__frost" aria-hidden="true"></span>
                <div class="marketing-dispatch-progress-floater__handle-content">
                    <x-filament::icon
                        icon="heroicon-o-arrows-pointing-out"
                        class="marketing-dispatch-progress-floater__handle-icon"
                    />
                    <span class="marketing-dispatch-progress-floater__handle-label">Envíos en curso</span>
                    <span class="marketing-dispatch-progress-floater__handle-hint">Arrastra</span>

                    <button
                        type="button"
                        class="marketing-dispatch-progress-floater__dismiss marketing-dispatch-progress-floater__dismiss--handle"
                        wire:click.prevent="dismissAll"
                        data-dispatch-dismiss-all
                        aria-label="Cerrar panel de progreso"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="size-4" />
                    </button>
                </div>
            </div>

            <div class="marketing-dispatch-progress-floater__stack">
                @foreach ($runs as $run)
                    @php
                        $status = DispatchProgressStatus::tryFrom($run['status'] ?? '') ?? DispatchProgressStatus::Processing;
                        $percent = (int) ($run['percent'] ?? 0);
                        $isProcessing = $status === DispatchProgressStatus::Processing;
                        $failedCount = (int) ($run['failed_recipients'] ?? 0);
                        $sentCount = (int) ($run['sent_recipients'] ?? 0);
                        $failures = is_array($run['failures'] ?? null) ? $run['failures'] : [];
                        $showLogHint = (bool) ($run['show_log_hint'] ?? false) || $failedCount > 0 || $failures !== [];
                        $logHint = filled($run['log_hint'] ?? null)
                            ? (string) $run['log_hint']
                            : DispatchProgressErrorPresenter::LogHint;
                        $statusClass = match ($status) {
                            DispatchProgressStatus::Completed => 'marketing-dispatch-progress-floater__card--completed',
                            DispatchProgressStatus::Partial => 'marketing-dispatch-progress-floater__card--partial',
                            DispatchProgressStatus::Failed => 'marketing-dispatch-progress-floater__card--failed',
                            default => 'marketing-dispatch-progress-floater__card--processing',
                        };
                    @endphp

                    <article
                        wire:key="dispatch-progress-{{ $run['id'] }}"
                        @class([
                            'marketing-dispatch-glass',
                            'marketing-dispatch-progress-floater__card',
                            $statusClass,
                        ])
                        @if ($isProcessing)
                            x-data="{
                                serverPercent: {{ $percent }},
                                sentCount: {{ $sentCount }},
                                totalRecipients: {{ (int) ($run['total_recipients'] ?? 0) }},
                                startedAt: {{ filled($run['started_at'] ?? null) ? (int) (strtotime((string) $run['started_at']) * 1000) : 'Date.now()' }},
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
                        @endif
                    >
                        <span class="marketing-dispatch-glass__frost" aria-hidden="true"></span>
                        <div class="marketing-dispatch-progress-floater__glow" aria-hidden="true"></div>

                        <header class="marketing-dispatch-progress-floater__header">
                            <div class="marketing-dispatch-progress-floater__status">
                                @if ($isProcessing)
                                    <span class="marketing-dispatch-progress-floater__spinner" aria-hidden="true"></span>
                                @else
                                    <span class="marketing-dispatch-progress-floater__status-icon" aria-hidden="true">
                                        @switch($status)
                                            @case(DispatchProgressStatus::Completed)
                                                <x-filament::icon icon="heroicon-o-check-circle" class="size-5" />
                                                @break
                                            @case(DispatchProgressStatus::Partial)
                                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-5" />
                                                @break
                                            @case(DispatchProgressStatus::Failed)
                                                <x-filament::icon icon="heroicon-o-x-circle" class="size-5" />
                                                @break
                                        @endswitch
                                    </span>
                                @endif

                                <div class="marketing-dispatch-progress-floater__meta">
                                    <p class="marketing-dispatch-progress-floater__title">
                                        {{ $run['title'] }}
                                    </p>
                                    <p class="marketing-dispatch-progress-floater__detail">
                                        {{ $run['detail'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="marketing-dispatch-progress-floater__actions">
                                <span class="marketing-dispatch-progress-floater__percent">
                                    @if ($isProcessing)
                                        <span x-text="displayPercent + '%'">{{ $percent }}%</span>
                                    @else
                                        {{ $percent }}%
                                    @endif
                                </span>

                                <button
                                    type="button"
                                    class="marketing-dispatch-progress-floater__dismiss"
                                    wire:click.prevent="dismissRun(@js($run['id']))"
                                    data-dispatch-dismiss-run="{{ $run['id'] }}"
                                    aria-label="Ocultar este envío"
                                >
                                    <x-filament::icon icon="heroicon-o-x-mark" class="size-4" />
                                </button>
                            </div>
                        </header>

                        <div
                            class="marketing-dispatch-progress-floater__track @if ($isProcessing && $percent <= 5 && $sentCount === 0) marketing-dispatch-progress-floater__track--indeterminate @endif"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            @if ($isProcessing)
                                x-bind:aria-valuenow="displayPercent"
                            @else
                                aria-valuenow="{{ $percent }}"
                            @endif
                        >
                            <div
                                class="marketing-dispatch-progress-floater__fill"
                                @if ($isProcessing)
                                    x-bind:style="'width: ' + Math.max(displayPercent, 5) + '%'"
                                @else
                                    style="width: {{ $percent }}%"
                                @endif
                            >
                                <span class="marketing-dispatch-progress-floater__knob" aria-hidden="true">
                                    <span class="marketing-dispatch-progress-floater__knob-core"></span>
                                    <span class="marketing-dispatch-progress-floater__knob-sparkles"></span>
                                </span>
                            </div>
                        </div>

                        <footer class="marketing-dispatch-progress-floater__footer">
                            <span class="marketing-dispatch-progress-floater__chip">
                                {{ $status->label() }}
                            </span>

                            @if (filled($run['current_channel'] ?? null))
                                <span class="marketing-dispatch-progress-floater__chip marketing-dispatch-progress-floater__chip--channel">
                                    {{ $run['current_channel'] }}
                                </span>
                            @endif

                            <span class="marketing-dispatch-progress-floater__chip marketing-dispatch-progress-floater__chip--muted">
                                {{ $sentCount }}/{{ (int) ($run['total_recipients'] ?? 0) }} enviados
                            </span>

                            @if ($failedCount > 0)
                                <span class="marketing-dispatch-progress-floater__chip marketing-dispatch-progress-floater__chip--failed">
                                    {{ $failedCount }} fallido{{ $failedCount === 1 ? '' : 's' }}
                                </span>
                            @endif
                        </footer>

                        @if ($showLogHint)
                            <div class="marketing-dispatch-progress-floater__log-hint">
                                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="size-3.5 shrink-0" />
                                <p>
                                    {{ $logHint }}
                                    <a href="{{ $dispatchLogUrl }}" class="marketing-dispatch-progress-floater__log-link">
                                        Ir al Historial de envíos
                                    </a>
                                </p>
                            </div>
                        @endif

                        @if ($failures !== [])
                            <ul class="marketing-dispatch-progress-floater__failures">
                                @foreach (array_slice($failures, -3) as $failure)
                                    @php
                                        $recipientRows = is_array($failure['recipients'] ?? null) ? $failure['recipients'] : [];
                                    @endphp
                                    <li>
                                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="size-3.5 shrink-0" />
                                        <span>
                                            {{ (int) ($failure['count'] ?? 0) }} fallido{{ ((int) ($failure['count'] ?? 0)) === 1 ? '' : 's' }}
                                            @if (filled($failure['channel'] ?? null))
                                                · {{ $failure['channel'] }}
                                            @endif
                                            @if (filled($failure['batch_number'] ?? null))
                                                · Lote {{ $failure['batch_number'] }}
                                            @endif
                                            — {{ $failure['detail'] ?? 'Error de envío' }}
                                        </span>
                                    </li>
                                    @foreach (array_slice($recipientRows, 0, 3) as $recipientFailure)
                                        <li class="marketing-dispatch-progress-floater__failure-recipient">
                                            <strong>{{ $recipientFailure['address'] ?? 'Destinatario' }}</strong>
                                            — {{ $recipientFailure['analyst_message'] ?? $recipientFailure['reason'] ?? 'Error de entrega' }}
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>

@script
<script>
    const shell = $el
    const storageKey = shell.dataset.positionKey

    let x = null
    let y = null
    let dragging = false
    let dragOffsetX = 0
    let dragOffsetY = 0

    const resolveWire = () => {
        if (typeof $wire !== 'undefined' && $wire) {
            return $wire
        }

        const wireId = shell.closest('[wire\\:id]')?.getAttribute('wire:id')

        if (wireId && window.Livewire) {
            return window.Livewire.find(wireId)
        }

        return null
    }

    const persistPosition = () => {
        if (x === null || y === null) {
            return
        }

        localStorage.setItem(storageKey, JSON.stringify({ x, y }))
    }

    const applyPosition = () => {
        if (x === null || y === null) {
            return
        }

        shell.style.left = `${x}px`
        shell.style.top = `${y}px`
        shell.style.right = 'auto'
        shell.style.bottom = 'auto'
    }

    const placeDefault = () => {
        const margin = 16
        const width = shell.offsetWidth || 448
        const height = shell.offsetHeight || 120

        x = Math.max(margin, window.innerWidth - width - margin)
        y = Math.max(margin, window.innerHeight - height - margin)
        applyPosition()
        persistPosition()
    }

    const clampToViewport = () => {
        if (x === null || y === null) {
            return
        }

        const margin = 8
        const width = shell.offsetWidth
        const height = shell.offsetHeight

        x = Math.max(margin, Math.min(x, window.innerWidth - width - margin))
        y = Math.max(margin, Math.min(y, window.innerHeight - height - margin))
        applyPosition()
        persistPosition()
    }

    const restorePosition = () => {
        try {
            const saved = localStorage.getItem(storageKey)

            if (! saved) {
                return false
            }

            const position = JSON.parse(saved)

            if (typeof position.x !== 'number' || typeof position.y !== 'number') {
                return false
            }

            x = position.x
            y = position.y
            applyPosition()

            return true
        } catch (error) {
            localStorage.removeItem(storageKey)

            return false
        }
    }

    if (! restorePosition()) {
        placeDefault()
    } else {
        clampToViewport()
    }

    if (! shell.dataset.dragBound) {
        shell.dataset.dragBound = '1'

        window.addEventListener('resize', clampToViewport)

        const onPointerMove = (event) => {
            if (! dragging) {
                return
            }

            const margin = 8
            const width = shell.offsetWidth
            const height = shell.offsetHeight

            x = Math.max(
                margin,
                Math.min(event.clientX - dragOffsetX, window.innerWidth - width - margin),
            )

            y = Math.max(
                margin,
                Math.min(event.clientY - dragOffsetY, window.innerHeight - height - margin),
            )

            applyPosition()
        }

        const endDrag = () => {
            if (! dragging) {
                return
            }

            dragging = false
            shell.classList.remove('marketing-dispatch-progress-floater-shell--dragging')
            window.removeEventListener('pointermove', onPointerMove)
            window.removeEventListener('pointerup', endDrag)
            window.removeEventListener('pointercancel', endDrag)
            persistPosition()
        }

        shell.addEventListener('pointerdown', (event) => {
            const handle = event.target.closest('[data-dispatch-progress-handle]')

            if (! handle || event.target.closest('button')) {
                return
            }

            event.preventDefault()

            const rect = shell.getBoundingClientRect()

            if (x === null || y === null) {
                x = rect.left
                y = rect.top
            }

            dragging = true
            dragOffsetX = event.clientX - rect.left
            dragOffsetY = event.clientY - rect.top
            shell.classList.add('marketing-dispatch-progress-floater-shell--dragging')

            window.addEventListener('pointermove', onPointerMove)
            window.addEventListener('pointerup', endDrag)
            window.addEventListener('pointercancel', endDrag)
        })

        shell.addEventListener('click', (event) => {
            const dismissAllButton = event.target.closest('[data-dispatch-dismiss-all]')

            if (dismissAllButton) {
                event.preventDefault()
                event.stopPropagation()
                resolveWire()?.call('dismissAll')

                return
            }

            const dismissRunButton = event.target.closest('[data-dispatch-dismiss-run]')

            if (dismissRunButton) {
                event.preventDefault()
                event.stopPropagation()
                resolveWire()?.call('dismissRun', dismissRunButton.dataset.dispatchDismissRun)
            }
        })
    }

    Livewire.hook('morph.updated', ({ el }) => {
        if (el !== shell && ! shell.contains(el)) {
            return
        }

        clampToViewport()
    })
</script>
@endscript
