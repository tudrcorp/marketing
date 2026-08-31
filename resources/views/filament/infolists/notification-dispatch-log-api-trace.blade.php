@php
    /** @var \App\Models\NotificationDispatchLog $record */
    $detail = $record->technical_detail ?? [];
    $apiCalls = is_array($detail['api_calls'] ?? null) ? $detail['api_calls'] : [];
    $notes = $detail['notes'] ?? null;
@endphp

<section class="marketing-dispatch-log-api-trace" aria-label="Respuestas de APIs involucradas">
    @if (filled($notes))
        <div class="marketing-dispatch-log-api-trace__note">
            <x-filament::icon icon="heroicon-o-information-circle" class="size-4 shrink-0" />
            <p>{{ $notes }}</p>
        </div>
    @endif

    @if ($apiCalls === [])
        <div class="marketing-dispatch-log-api-trace__empty">
            <x-filament::icon icon="heroicon-o-server" class="marketing-dispatch-log-api-trace__empty-icon" />
            <p class="marketing-dispatch-log-api-trace__empty-title">Sin llamadas al API registradas</p>
            <p class="marketing-dispatch-log-api-trace__empty-copy">
                Este evento se resolvió localmente o el registro es anterior a la traza estructurada.
            </p>
        </div>
    @else
        <div class="marketing-dispatch-log-api-trace__list">
            @foreach ($apiCalls as $index => $call)
                @php
                    $response = is_array($call['response'] ?? null) ? $call['response'] : null;
                    $status = $response['status'] ?? null;
                    $successful = (bool) ($response['successful'] ?? false);
                    $statusClass = $successful ? 'marketing-dispatch-log-api-trace__status--ok' : 'marketing-dispatch-log-api-trace__status--error';
                @endphp

                <article class="marketing-dispatch-log-api-trace__card">
                    <header class="marketing-dispatch-log-api-trace__card-header">
                        <div class="marketing-dispatch-log-api-trace__card-heading">
                            <span class="marketing-dispatch-log-api-trace__index">API {{ $index + 1 }}</span>
                            <h4 class="marketing-dispatch-log-api-trace__label">{{ $call['label'] ?? 'Llamada al API' }}</h4>
                            <p class="marketing-dispatch-log-api-trace__endpoint">
                                <span class="marketing-dispatch-log-api-trace__method">{{ $call['method'] ?? 'POST' }}</span>
                                {{ $call['endpoint'] ?? '—' }}
                            </p>
                        </div>

                        @if ($status !== null)
                            <span @class(['marketing-dispatch-log-api-trace__status', $statusClass])>
                                HTTP {{ $status }}
                            </span>
                        @elseif (filled($call['error']['message'] ?? null))
                            <span class="marketing-dispatch-log-api-trace__status marketing-dispatch-log-api-trace__status--error">
                                Sin respuesta
                            </span>
                        @endif
                    </header>

                    <div class="marketing-dispatch-log-api-trace__grid">
                        <div class="marketing-dispatch-log-api-trace__panel">
                            <h5 class="marketing-dispatch-log-api-trace__panel-title">Request</h5>
                            <pre class="marketing-dispatch-log-api-trace__code">{{ json_encode($call['request'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>

                        <div class="marketing-dispatch-log-api-trace__panel">
                            <h5 class="marketing-dispatch-log-api-trace__panel-title">Response</h5>
                            @if ($response !== null)
                                <pre class="marketing-dispatch-log-api-trace__code">{{ json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            @elseif (filled($call['error'] ?? null))
                                <pre class="marketing-dispatch-log-api-trace__code marketing-dispatch-log-api-trace__code--error">{{ json_encode($call['error'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <p class="marketing-dispatch-log-api-trace__placeholder">—</p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if (filled($detail) && count($detail) > count($apiCalls) + (filled($notes) ? 1 : 0))
        <details class="marketing-dispatch-log-api-trace__raw">
            <summary>Metadatos adicionales</summary>
            <pre class="marketing-dispatch-log-api-trace__code">{{ json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    @endif
</section>
