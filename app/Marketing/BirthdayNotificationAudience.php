<?php

namespace App\Marketing;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum BirthdayNotificationAudience: string implements HasIcon, HasLabel
{
    case BrokerAgents = 'broker_agents';
    case BrokerAgencies = 'broker_agencies';
    case TravelAgents = 'travel_agents';
    case TravelAgencies = 'travel_agencies';
    case IndividualAffiliates = 'individual_affiliates';
    case CorporateAffiliates = 'corporate_affiliates';
    case Collaborators = 'collaborators';
    case Doctors = 'doctors';
    case NaturalSuppliers = 'natural_suppliers';
    case LegalSuppliers = 'legal_suppliers';
    case ClientGroups = 'client_groups';

    public function getLabel(): string
    {
        return match ($this) {
            self::BrokerAgents => 'Agentes de corretaje',
            self::BrokerAgencies => 'Agencias de corretaje',
            self::TravelAgents => 'Agentes de viajes',
            self::TravelAgencies => 'Agencias de viajes',
            self::IndividualAffiliates => 'Afiliados individuales',
            self::CorporateAffiliates => 'Afiliados corporativos',
            self::Collaborators => 'Colaboradores',
            self::Doctors => 'Doctores',
            self::NaturalSuppliers => 'Proveedores naturales',
            self::LegalSuppliers => 'Proveedores jurídicos',
            self::ClientGroups => 'Grupos de clientes',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::BrokerAgents, self::TravelAgents => Heroicon::OutlinedUsers,
            self::BrokerAgencies, self::TravelAgencies => Heroicon::OutlinedBuildingOffice2,
            self::IndividualAffiliates => Heroicon::OutlinedUser,
            self::CorporateAffiliates => Heroicon::OutlinedBuildingLibrary,
            self::Collaborators => Heroicon::OutlinedUserGroup,
            self::Doctors => Heroicon::OutlinedHeart,
            self::NaturalSuppliers => Heroicon::OutlinedUserCircle,
            self::LegalSuppliers => Heroicon::OutlinedBuildingOffice,
            self::ClientGroups => Heroicon::OutlinedUserGroup,
        };
    }
}
