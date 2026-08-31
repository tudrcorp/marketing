@php
    /** @var array{total: int, failures: int, partial: int, sent: int, attention: int} $stats */
@endphp

<section class="marketing-dispatch-log-stats" aria-label="Resumen de envíos de hoy">
    <div class="marketing-dispatch-log-stats__glow" aria-hidden="true"></div>

    <header class="marketing-dispatch-log-stats__header">
        <div class="marketing-dispatch-log-stats__heading">
            <div class="marketing-dispatch-log-stats__icon-wrap">
                <x-filament::icon icon="heroicon-o-signal" class="marketing-dispatch-log-stats__icon" />
            </div>
            <div>
                <h2 class="marketing-dispatch-log-stats__title">Centro de control de envíos</h2>
                <p class="marketing-dispatch-log-stats__subtitle">
                    Resumen de hoy. Cada falla incluye causa y pasos para resolverla.
                </p>
            </div>
        </div>
    </header>

    <div class="marketing-dispatch-log-stats__grid">
        <article class="marketing-dispatch-log-stats__card marketing-dispatch-log-stats__card--attention">
            <p class="marketing-dispatch-log-stats__card-label">Requieren atención</p>
            <p class="marketing-dispatch-log-stats__card-value">{{ $stats['attention'] }}</p>
            <p class="marketing-dispatch-log-stats__card-hint">Fallos, parciales u omitidos</p>
        </article>

        <article class="marketing-dispatch-log-stats__card marketing-dispatch-log-stats__card--danger">
            <p class="marketing-dispatch-log-stats__card-label">Fallidos</p>
            <p class="marketing-dispatch-log-stats__card-value">{{ $stats['failures'] }}</p>
            <p class="marketing-dispatch-log-stats__card-hint">Revisa el detalle y corrige</p>
        </article>

        <article class="marketing-dispatch-log-stats__card marketing-dispatch-log-stats__card--warning">
            <p class="marketing-dispatch-log-stats__card-label">Parciales</p>
            <p class="marketing-dispatch-log-stats__card-value">{{ $stats['partial'] }}</p>
            <p class="marketing-dispatch-log-stats__card-hint">Algunos destinatarios no recibieron</p>
        </article>

        <article class="marketing-dispatch-log-stats__card marketing-dispatch-log-stats__card--success">
            <p class="marketing-dispatch-log-stats__card-label">Completados</p>
            <p class="marketing-dispatch-log-stats__card-value">{{ $stats['sent'] }}</p>
            <p class="marketing-dispatch-log-stats__card-hint">Enviados o en cola</p>
        </article>
    </div>
</section>
