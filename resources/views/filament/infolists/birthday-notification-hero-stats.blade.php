@php
    use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;

    $stats = BirthdayNotificationPresentation::stats($record);
@endphp

<div class="marketing-birthday-hero-stats">
    <article class="marketing-birthday-stat-card">
        <span class="marketing-birthday-stat-card__icon marketing-birthday-stat-card__icon--channels">
            <x-filament::icon icon="heroicon-o-paper-airplane" class="size-5" />
        </span>
        <div class="marketing-birthday-stat-card__body">
            <p class="marketing-birthday-stat-card__value">{{ $stats['channels'] }}</p>
            <p class="marketing-birthday-stat-card__label">Canales</p>
        </div>
    </article>

    <article class="marketing-birthday-stat-card">
        <span class="marketing-birthday-stat-card__icon marketing-birthday-stat-card__icon--audiences">
            <x-filament::icon icon="heroicon-o-user-group" class="size-5" />
        </span>
        <div class="marketing-birthday-stat-card__body">
            <p class="marketing-birthday-stat-card__value">{{ $stats['audiences'] }}</p>
            <p class="marketing-birthday-stat-card__label">Audiencias</p>
        </div>
    </article>

    <article class="marketing-birthday-stat-card">
        <span class="marketing-birthday-stat-card__icon marketing-birthday-stat-card__icon--copy">
            <x-filament::icon icon="heroicon-o-chat-bubble-bottom-center-text" class="size-5" />
        </span>
        <div class="marketing-birthday-stat-card__body">
            <p class="marketing-birthday-stat-card__value">{{ number_format($stats['copy_length']) }}</p>
            <p class="marketing-birthday-stat-card__label">Caracteres</p>
        </div>
    </article>

    <article class="marketing-birthday-stat-card">
        <span @class([
            'marketing-birthday-stat-card__icon',
            'marketing-birthday-stat-card__icon--image' => $stats['has_image'],
            'marketing-birthday-stat-card__icon--image-empty' => ! $stats['has_image'],
        ])>
            <x-filament::icon icon="heroicon-o-photo" class="size-5" />
        </span>
        <div class="marketing-birthday-stat-card__body">
            <p class="marketing-birthday-stat-card__value">{{ $stats['has_image'] ? 'Sí' : 'No' }}</p>
            <p class="marketing-birthday-stat-card__label">Imagen</p>
        </div>
    </article>
</div>
