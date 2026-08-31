<?php

namespace App\Filament\Resources\BirthdayNotifications\Support;

use App\Marketing\BirthdayNotificationAudience;
use App\Marketing\BirthdayNotificationChannel;
use App\Models\BirthdayNotification;
use Illuminate\Support\Facades\Storage;

class BirthdayNotificationPresentation
{
    /**
     * @return array{
     *     channels: int,
     *     audiences: int,
     *     copy_length: int,
     *     has_image: bool,
     *     is_ready: bool,
     * }
     */
    public static function stats(BirthdayNotification $record): array
    {
        $channels = count($record->channelEnums());
        $audiences = count($record->audienceEnums());
        $copyLength = mb_strlen($record->copy ?? '');
        $hasImage = filled($record->image);

        return [
            'channels' => $channels,
            'audiences' => $audiences,
            'copy_length' => $copyLength,
            'has_image' => $hasImage,
            'is_ready' => $copyLength > 0 && $channels > 0 && $audiences > 0,
        ];
    }

    public static function imageUrl(BirthdayNotification $record): ?string
    {
        if (! filled($record->image)) {
            return null;
        }

        return Storage::disk('public')->url($record->image);
    }

    /**
     * @return list<array{label: string, color: string, icon: string}>
     */
    public static function channelCards(BirthdayNotification $record): array
    {
        return collect($record->channelEnums())
            ->map(fn (BirthdayNotificationChannel $channel): array => [
                'label' => $channel->getLabel(),
                'color' => $channel->getColor(),
                'icon' => 'heroicon-'.$channel->getIcon()->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     label: string,
     *     icon: string,
     *     items: list<array{label: string, icon: string}>,
     * }>
     */
    public static function audienceGroups(BirthdayNotification $record): array
    {
        $selected = collect($record->audienceEnums())->keyBy(fn (BirthdayNotificationAudience $audience): string => $audience->value);

        $groups = [
            [
                'label' => 'Corretaje',
                'icon' => 'heroicon-o-building-office-2',
                'audiences' => [
                    BirthdayNotificationAudience::BrokerAgents,
                    BirthdayNotificationAudience::BrokerAgencies,
                ],
            ],
            [
                'label' => 'Viajes',
                'icon' => 'heroicon-o-globe-americas',
                'audiences' => [
                    BirthdayNotificationAudience::TravelAgents,
                    BirthdayNotificationAudience::TravelAgencies,
                ],
            ],
            [
                'label' => 'Afiliados',
                'icon' => 'heroicon-o-user-group',
                'audiences' => [
                    BirthdayNotificationAudience::IndividualAffiliates,
                    BirthdayNotificationAudience::CorporateAffiliates,
                ],
            ],
            [
                'label' => 'Internos',
                'icon' => 'heroicon-o-heart',
                'audiences' => [
                    BirthdayNotificationAudience::Collaborators,
                    BirthdayNotificationAudience::Doctors,
                ],
            ],
            [
                'label' => 'Proveedores',
                'icon' => 'heroicon-o-briefcase',
                'audiences' => [
                    BirthdayNotificationAudience::NaturalSuppliers,
                    BirthdayNotificationAudience::LegalSuppliers,
                ],
            ],
        ];

        return collect($groups)
            ->map(function (array $group) use ($selected): ?array {
                $items = collect($group['audiences'])
                    ->filter(fn (BirthdayNotificationAudience $audience): bool => $selected->has($audience->value))
                    ->map(fn (BirthdayNotificationAudience $audience): array => [
                        'label' => $audience->getLabel(),
                        'icon' => 'heroicon-'.$audience->getIcon()->value,
                    ])
                    ->values()
                    ->all();

                if ($items === []) {
                    return null;
                }

                return [
                    'label' => $group['label'],
                    'icon' => $group['icon'],
                    'items' => $items,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, met: bool, hint: string}>
     */
    public static function readinessChecks(BirthdayNotification $record): array
    {
        $stats = self::stats($record);

        return [
            [
                'label' => 'Mensaje configurado',
                'met' => $stats['copy_length'] > 0,
                'hint' => $stats['copy_length'] > 0
                    ? "{$stats['copy_length']} caracteres"
                    : 'Agrega el texto del mensaje',
            ],
            [
                'label' => 'Canales de envío',
                'met' => $stats['channels'] > 0,
                'hint' => $stats['channels'] > 0
                    ? "{$stats['channels']} canal".($stats['channels'] === 1 ? '' : 'es').' seleccionado'.($stats['channels'] === 1 ? '' : 's')
                    : 'Selecciona al menos un canal',
            ],
            [
                'label' => 'Grupos destinatarios',
                'met' => $stats['audiences'] > 0,
                'hint' => $stats['audiences'] > 0
                    ? "{$stats['audiences']} grupo".($stats['audiences'] === 1 ? '' : 's').' destinatario'.($stats['audiences'] === 1 ? '' : 's')
                    : 'Selecciona al menos un grupo',
            ],
            [
                'label' => 'Imagen adjunta',
                'met' => $stats['has_image'],
                'hint' => $stats['has_image'] ? 'Imagen lista para el envío' : 'Opcional — sin imagen configurada',
            ],
        ];
    }

    public static function readinessLabel(BirthdayNotification $record): string
    {
        return self::stats($record)['is_ready'] ? 'Lista para envío' : 'Configuración incompleta';
    }

    public static function readinessColor(BirthdayNotification $record): string
    {
        return self::stats($record)['is_ready'] ? 'success' : 'warning';
    }
}
