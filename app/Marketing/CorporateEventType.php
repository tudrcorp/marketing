<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CorporateEventType: string implements HasColor, HasIcon, HasLabel
{
    case EducationalSeminar = 'educational_seminar';
    case SalesTraining = 'sales_training';
    case PublicCorporateMeeting = 'public_corporate_meeting';
    case BusinessCapture = 'business_capture';
    case NetworkingFair = 'networking_fair';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::EducationalSeminar => 'Seminario educativo',
            self::SalesTraining => 'Capacitación comercial',
            self::PublicCorporateMeeting => 'Reunión corporativa pública',
            self::BusinessCapture => 'Actividad de captación',
            self::NetworkingFair => 'Networking / feria',
            self::Other => 'Otro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EducationalSeminar => 'info',
            self::SalesTraining => 'warning',
            self::PublicCorporateMeeting => 'primary',
            self::BusinessCapture => 'success',
            self::NetworkingFair => 'danger',
            self::Other => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::EducationalSeminar => Heroicon::OutlinedAcademicCap,
            self::SalesTraining => Heroicon::OutlinedPresentationChartLine,
            self::PublicCorporateMeeting => Heroicon::OutlinedBuildingOffice2,
            self::BusinessCapture => Heroicon::OutlinedUserPlus,
            self::NetworkingFair => Heroicon::OutlinedGlobeAmericas,
            self::Other => Heroicon::OutlinedSparkles,
        };
    }
}
