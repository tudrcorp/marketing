<?php

namespace App\Filament\Resources\CorporateEvents\Support;

use App\Models\CorporateEvent;
use Illuminate\Support\Facades\Storage;

class CorporateEventPresentation
{
    /**
     * @return array{
     *     audiences: int,
     *     registrations: int,
     *     capacity_label: string,
     *     attendance_rate: ?float,
     * }
     */
    public static function stats(CorporateEvent $event): array
    {
        return [
            'audiences' => count($event->target_audiences ?? []),
            'registrations' => $event->registrations_count,
            'capacity_label' => $event->hasCapacity()
                ? "{$event->registrations_count}/{$event->capacity}"
                : (string) $event->registrations_count,
            'attendance_rate' => $event->attendanceRate(),
        ];
    }

    public static function capacityColor(CorporateEvent $event): string
    {
        if (! $event->hasCapacity()) {
            return 'gray';
        }

        if ($event->isFull()) {
            return 'danger';
        }

        $ratio = $event->registrations_count / max(1, $event->capacity);

        return match (true) {
            $ratio >= 0.85 => 'warning',
            default => 'success',
        };
    }

    public static function hasCoverImage(CorporateEvent $event): bool
    {
        return filled($event->cover_image)
            && Storage::disk('public')->exists($event->cover_image);
    }

    public static function coverImageUrl(CorporateEvent $event): ?string
    {
        if (! self::hasCoverImage($event)) {
            return null;
        }

        return Storage::disk('public')->url($event->cover_image);
    }
}
