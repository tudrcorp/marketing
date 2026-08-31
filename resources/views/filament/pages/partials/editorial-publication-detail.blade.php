@php
    $scheduledAt = $publication->scheduled_at->timezone(config('app.timezone'));
    $referenceImageUrl = filled($publication->reference_image)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($publication->reference_image)
        : null;
@endphp

<div class="marketing-agenda__detail">
    <div class="marketing-agenda__detail-meta">
        <span class="marketing-agenda__pub-badge marketing-agenda__pub-badge--platform">
            {{ $publication->socialAccount->platform->getLabel() }}
        </span>
        <span @class([
            'marketing-agenda__pub-badge',
            'marketing-agenda__pub-badge--status',
            'marketing-agenda__pub-badge--' . $publication->status->value,
        ])>
            {{ $publication->status->getLabel() }}
        </span>
    </div>

    <dl class="marketing-agenda__detail-grid">
        <div>
            <dt>Cuenta</dt>
            <dd>{{ $publication->socialAccount->name }}</dd>
        </div>
        <div>
            <dt>Horario</dt>
            <dd>{{ $scheduledAt->format('H:i') }}</dd>
        </div>
        @if ($publication->createdBy)
            <div>
                <dt>Creado por</dt>
                <dd>{{ $publication->createdBy->name }}</dd>
            </div>
        @endif
    </dl>

    @if ($referenceImageUrl)
        <figure class="marketing-agenda__detail-figure">
            <img
                src="{{ $referenceImageUrl }}"
                alt="Imagen de referencia de {{ $publication->title }}"
                class="marketing-agenda__detail-image"
            />
            <figcaption>Imagen de referencia</figcaption>
        </figure>
    @endif

    <section class="marketing-agenda__detail-section">
        <h4>Contenido</h4>
        <p class="marketing-agenda__detail-body">{{ $publication->body }}</p>
    </section>

    @if ($canManage)
        <div class="marketing-agenda__detail-actions">
            <button
                type="button"
                wire:click="mountAction('editPublication', { publication: {{ $publication->id }} })"
                class="marketing-agenda__detail-edit"
            >
                Editar publicación
            </button>
        </div>
    @endif
</div>
