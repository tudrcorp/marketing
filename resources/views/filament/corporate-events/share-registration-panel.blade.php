@php
    use App\Filament\Resources\CorporateEvents\Support\CorporateEventSharePresentation;

    $presentation = app(CorporateEventSharePresentation::class);
    $registrationUrl = $record->registration_url;
    $statusLabel = $presentation->registrationStatusLabel($record);
    $statusColor = $presentation->registrationStatusColor($record);
@endphp

@if (filled($registrationUrl))
    <section
        class="marketing-corporate-event-share marketing-corporate-event-share--modal"
        x-data="{
            copied: false,
            copying: false,
            async copyUrl() {
                if (this.copying) {
                    return;
                }

                this.copying = true;

                try {
                    await navigator.clipboard.writeText(@js($registrationUrl));
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 2600);
                } catch (error) {
                    const input = this.$refs.urlInput;
                    input.select();
                    document.execCommand('copy');
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 2600);
                } finally {
                    this.copying = false;
                }
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
                        Copia el enlace o envíalo directamente al destinatario.
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
            <div class="marketing-corporate-event-share__url-field">
                <label for="corporate-event-registration-url" class="marketing-corporate-event-share__url-label">
                    URL pública
                </label>
                <input
                    id="corporate-event-registration-url"
                    type="text"
                    readonly
                    x-ref="urlInput"
                    value="{{ $registrationUrl }}"
                    class="marketing-corporate-event-share__url-input"
                    x-on:click="$refs.urlInput.select()"
                >
            </div>

            <button
                type="button"
                class="marketing-corporate-event-share__copy"
                x-bind:class="{
                    'marketing-corporate-event-share__copy--copied': copied,
                    'marketing-corporate-event-share__copy--copying': copying,
                }"
                x-bind:disabled="copying"
                x-on:click="copyUrl()"
                x-bind:aria-label="copied ? 'Enlace copiado' : 'Copiar enlace de inscripción'"
            >
                <span class="marketing-corporate-event-share__copy-state" x-show="! copied && ! copying">
                    <x-filament::icon icon="heroicon-m-clipboard-document" class="size-4" />
                    Copiar enlace
                </span>
                <span class="marketing-corporate-event-share__copy-state" x-show="copying" x-cloak>
                    <x-filament::icon icon="heroicon-m-arrow-path" class="size-4 marketing-corporate-event-share__copy-spinner" />
                    Copiando…
                </span>
                <span class="marketing-corporate-event-share__copy-state" x-show="copied" x-cloak>
                    <x-filament::icon icon="heroicon-m-check-circle" class="size-4" />
                    ¡Copiado!
                </span>
            </button>
        </div>
    </section>
@endif
