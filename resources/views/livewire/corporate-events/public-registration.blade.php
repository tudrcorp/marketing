@php
    $event = $this->event;
@endphp

<div class="event-registration">
    <div class="event-registration__backdrop" aria-hidden="true">
        <span class="event-registration__orb event-registration__orb--primary"></span>
        <span class="event-registration__orb event-registration__orb--secondary"></span>
    </div>

    <main class="event-registration__shell">
        @if ($event === null)
            <section class="event-registration__card event-registration__card--closed">
                <h1>Enlace no válido</h1>
                <p>La invitación que intentas abrir no existe o fue retirada.</p>
            </section>
        @else
            <section class="event-registration__card">
                <header class="event-registration__header">
                    <img
                        src="{{ asset('images/logos/logoNewTDG.png') }}"
                        alt="TDG"
                        class="event-registration__logo"
                        width="180"
                        height="48"
                    />

                    <div class="event-registration__event-meta">
                        @if ($event->typeEnum())
                            <span class="event-registration__chip event-registration__chip--{{ $event->typeEnum()->getColor() }}">
                                {{ $event->typeEnum()->getLabel() }}
                            </span>
                        @endif

                        @if ($event->modalityEnum())
                            <span class="event-registration__chip event-registration__chip--neutral">
                                {{ $event->modalityEnum()->getLabel() }}
                            </span>
                        @endif
                    </div>

                    <h1 class="event-registration__title">{{ $event->title }}</h1>

                    @if (filled($event->summary))
                        <p class="event-registration__summary">{{ $event->summary }}</p>
                    @endif

                    <dl class="event-registration__facts">
                        <div class="event-registration__fact">
                            <dt>Fecha</dt>
                            <dd>{{ $event->starts_at->timezone(config('app.timezone'))->locale('es')->isoFormat('dddd D [de] MMMM · HH:mm') }}</dd>
                        </div>

                        @if (filled($event->venue_name))
                            <div class="event-registration__fact">
                                <dt>Lugar</dt>
                                <dd>{{ $event->venue_name }}</dd>
                            </div>
                        @endif

                        @if ($this->deadlineLabel)
                            <div class="event-registration__fact">
                                <dt>Cierre de inscripciones</dt>
                                <dd>{{ $this->deadlineLabel }}</dd>
                            </div>
                        @endif

                        @if ($event->hasCapacity())
                            <div class="event-registration__fact">
                                <dt>Cupos</dt>
                                <dd>{{ $event->remainingCapacity() }} disponibles de {{ $event->capacity }}</dd>
                            </div>
                        @endif
                    </dl>
                </header>

                @if ($submitted)
                    <div class="event-registration__success" role="status">
                        <div class="event-registration__success-icon" aria-hidden="true">✓</div>
                        <h2>¡Inscripción confirmada!</h2>
                        <p>Recibimos tu solicitud para <strong>{{ $event->title }}</strong>. Te contactaremos si necesitamos información adicional.</p>
                    </div>
                @elseif ($this->closedReason)
                    <div class="event-registration__closed" role="alert">
                        <h2>Inscripciones cerradas</h2>
                        <p>{{ $this->closedReason }}</p>
                    </div>
                @else
                    <form wire:submit="submit" class="event-registration__form" novalidate>
                        <div class="event-registration__form-intro">
                            <h2>Completa tus datos</h2>
                            <p>Los campos marcados con <span aria-hidden="true">*</span> son obligatorios.</p>
                        </div>

                        <div class="event-registration__field">
                            <label for="full_name">Nombre y apellido o razón social <span>*</span></label>
                            <input
                                id="full_name"
                                type="text"
                                wire:model="full_name"
                                autocomplete="name"
                                placeholder="Ej. María Pérez o Inversiones TDG C.A."
                                @class(['event-registration__input', 'event-registration__input--error' => $errors->has('full_name')])
                            >
                            @error('full_name')
                                <p class="event-registration__error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="event-registration__field">
                            <label for="document_id">Cédula de identidad o RIF <span>*</span></label>
                            <input
                                id="document_id"
                                type="text"
                                wire:model="document_id"
                                autocomplete="off"
                                placeholder="Ej. V-12345678 o J-12345678-9"
                                @class(['event-registration__input', 'event-registration__input--error' => $errors->has('document_id')])
                            >
                            @error('document_id')
                                <p class="event-registration__error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="event-registration__field-grid">
                            <div class="event-registration__field">
                                <label for="phone">Nro. de teléfono <span>*</span></label>
                                <input
                                    id="phone"
                                    type="tel"
                                    wire:model="phone"
                                    autocomplete="tel"
                                    placeholder="Ej. 04141234567"
                                    @class(['event-registration__input', 'event-registration__input--error' => $errors->has('phone')])
                                >
                                @error('phone')
                                    <p class="event-registration__error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="event-registration__field">
                                <label for="email">Correo electrónico <span>*</span></label>
                                <input
                                    id="email"
                                    type="email"
                                    wire:model="email"
                                    autocomplete="email"
                                    placeholder="correo@ejemplo.com"
                                    @class(['event-registration__input', 'event-registration__input--error' => $errors->has('email')])
                                >
                                @error('email')
                                    <p class="event-registration__error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @error('registration')
                            <p class="event-registration__error event-registration__error--banner">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            class="event-registration__submit"
                            wire:loading.attr="disabled"
                            wire:target="submit"
                        >
                            <span wire:loading.remove wire:target="submit">Confirmar inscripción</span>
                            <span wire:loading wire:target="submit">Procesando…</span>
                        </button>
                    </form>
                @endif
            </section>

            <p class="event-registration__footer">TDG · Marketing corporativo</p>
        @endif
    </main>
</div>
