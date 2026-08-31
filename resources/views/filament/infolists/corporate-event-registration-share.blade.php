@php
    use App\Filament\Resources\CorporateEvents\Support\CorporateEventSharePresentation;

    $presentation = app(CorporateEventSharePresentation::class);
    $registrationUrl = $record->registration_url;
    $shareMessage = $presentation->shareMessage($record);
    $statusLabel = $presentation->registrationStatusLabel($record);
    $statusColor = $presentation->registrationStatusColor($record);
@endphp

@if (filled($registrationUrl))
    <section
        class="marketing-corporate-event-share marketing-corporate-event-share--summary"
        x-data="{
            copied: false,
            async copyUrl() {
                try {
                    await navigator.clipboard.writeText(@js($registrationUrl));
                } catch (error) {
                    const input = this.$refs.urlInput;
                    input.select();
                    document.execCommand('copy');
                }

                this.copied = true;
                setTimeout(() => this.copied = false, 2200);
            },
        }"
    >
        <div class="marketing-corporate-event-share__glow" aria-hidden="true"></div>

        <header class="marketing-corporate-event-share__header">
            <div class="marketing-corporate-event-share__heading">
                <span class="marketing-corporate-event-share__icon-wrap">
                    <x-filament::icon icon="heroicon-o-link" class="size-5" />
                </span>
                <div>
                    <h3 class="marketing-corporate-event-share__title">Enlace de inscripción</h3>
                    <p class="marketing-corporate-event-share__subtitle">
                        Usa <strong>Compartir inscripción</strong> para personalizar el mensaje y enviarlo por correo, WhatsApp o SMS.
                    </p>
                </div>
            </div>

            <span @class([
                'marketing-corporate-event-share__status',
                'marketing-corporate-event-share__status--' . $statusColor,
            ])>
                {{ $statusLabel }}
            </span>
        </header>

        <div class="marketing-corporate-event-share__url-shell">
            <input
                type="text"
                readonly
                x-ref="urlInput"
                value="{{ $registrationUrl }}"
                class="marketing-corporate-event-share__url-input"
            >

            <button
                type="button"
                class="marketing-corporate-event-share__copy"
                x-bind:class="{ 'marketing-corporate-event-share__copy--copied': copied }"
                x-on:click="copyUrl()"
            >
                <span x-show="! copied">
                    <x-filament::icon icon="heroicon-m-clipboard-document" class="size-4" />
                    Copiar enlace
                </span>
                <span x-show="copied" x-cloak>
                    <x-filament::icon icon="heroicon-m-check-circle" class="size-4" />
                    ¡Copiado!
                </span>
            </button>
        </div>

        <details class="marketing-corporate-event-share__preview">
            <summary>Mensaje sugerido</summary>
            <pre class="marketing-corporate-event-share__preview-copy">{{ $shareMessage }}</pre>
        </details>
    </section>
@else
    <section class="marketing-corporate-event-share marketing-corporate-event-share--empty">
        <p>Guarda el evento para generar el enlace público de inscripción.</p>
    </section>
@endif
