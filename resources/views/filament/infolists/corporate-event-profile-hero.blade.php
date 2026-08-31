@php
    use App\Filament\Resources\CorporateEvents\Support\CorporateEventPresentation;
    use App\Marketing\CorporateEventModality;
    use App\Marketing\CorporateEventStatus;
    use App\Marketing\CorporateEventType;

    $stats = CorporateEventPresentation::stats($record);
    $status = $record->statusEnum();
    $eventType = CorporateEventType::tryFrom((string) $record->event_type);
    $modality = CorporateEventModality::tryFrom((string) $record->modality);
    $capacityColor = CorporateEventPresentation::capacityColor($record);
    $attendanceRate = $stats['attendance_rate'];
@endphp

<div class="marketing-corporate-event-hero">
    <div class="marketing-corporate-event-hero__head">
        <h2 class="marketing-corporate-event-hero__title">
            {{ $record->title }}
        </h2>

        <div class="marketing-corporate-event-hero__badges">
            <span @class([
                'marketing-corporate-event-badge',
                'marketing-corporate-event-badge--'.$status->getColor(),
            ])>
                {{ $status->getLabel() }}
            </span>

            @if (filled($record->code))
                <span class="marketing-corporate-event-badge marketing-corporate-event-badge--gray">
                    {{ $record->code }}
                </span>
            @endif
        </div>
    </div>

    <div class="marketing-corporate-event-hero-stats">
        <article class="marketing-corporate-event-stat-card">
            <span @class([
                'marketing-corporate-event-stat-card__icon',
                'marketing-corporate-event-stat-card__icon--'.$capacityColor,
            ])>
                <x-filament::icon icon="heroicon-o-user-plus" class="size-5" />
            </span>
            <div class="marketing-corporate-event-stat-card__body">
                <p class="marketing-corporate-event-stat-card__value">{{ $stats['capacity_label'] }}</p>
                <p class="marketing-corporate-event-stat-card__label">Inscritos</p>
            </div>
        </article>

        <article class="marketing-corporate-event-stat-card">
            <span @class([
                'marketing-corporate-event-stat-card__icon',
                'marketing-corporate-event-stat-card__icon--'.($attendanceRate === null ? 'gray' : 'info'),
            ])>
                <x-filament::icon icon="heroicon-o-chart-bar" class="size-5" />
            </span>
            <div class="marketing-corporate-event-stat-card__body">
                <p class="marketing-corporate-event-stat-card__value">
                    {{ $attendanceRate === null ? '—' : $attendanceRate.'%' }}
                </p>
                <p class="marketing-corporate-event-stat-card__label">Asistencia</p>
            </div>
        </article>

        <article class="marketing-corporate-event-stat-card">
            <span class="marketing-corporate-event-stat-card__icon marketing-corporate-event-stat-card__icon--primary">
                <x-filament::icon icon="heroicon-o-user-group" class="size-5" />
            </span>
            <div class="marketing-corporate-event-stat-card__body">
                <p class="marketing-corporate-event-stat-card__value">{{ $stats['audiences'] }}</p>
                <p class="marketing-corporate-event-stat-card__label">Audiencias</p>
            </div>
        </article>

        <article class="marketing-corporate-event-stat-card">
            <span class="marketing-corporate-event-stat-card__icon marketing-corporate-event-stat-card__icon--warning">
                <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
            </span>
            <div class="marketing-corporate-event-stat-card__body">
                <p class="marketing-corporate-event-stat-card__value">
                    {{ $record->starts_at?->format('d/m/Y') ?? '—' }}
                </p>
                <p class="marketing-corporate-event-stat-card__label">
                    {{ $record->starts_at?->format('H:i') ?? 'Inicio' }}
                </p>
            </div>
        </article>
    </div>

    <div class="marketing-corporate-event-hero__meta">
        <div class="marketing-corporate-event-hero__meta-item">
            <x-filament::icon icon="heroicon-o-user" class="marketing-corporate-event-hero__meta-icon" />
            <div>
                <p class="marketing-corporate-event-hero__meta-label">Creado por</p>
                <p class="marketing-corporate-event-hero__meta-value">{{ $record->createdBy?->name ?? '—' }}</p>
            </div>
        </div>

        @if ($eventType)
            <div class="marketing-corporate-event-hero__meta-item">
                <x-filament::icon icon="heroicon-o-tag" class="marketing-corporate-event-hero__meta-icon" />
                <div>
                    <p class="marketing-corporate-event-hero__meta-label">Tipo</p>
                    <p class="marketing-corporate-event-hero__meta-value">{{ $eventType->getLabel() }}</p>
                </div>
            </div>
        @endif

        @if ($modality)
            <div class="marketing-corporate-event-hero__meta-item">
                <x-filament::icon icon="heroicon-o-signal" class="marketing-corporate-event-hero__meta-icon" />
                <div>
                    <p class="marketing-corporate-event-hero__meta-label">Modalidad</p>
                    <p class="marketing-corporate-event-hero__meta-value">{{ $modality->getLabel() }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
