<?php

namespace App\Marketing;

use App\Services\Marketing\MarketingAgentsApiService;
use App\Services\Marketing\MarketingCorporateAffiliatesApiService;
use App\Services\Marketing\MarketingIndividualAffiliatesApiService;
use App\Services\Marketing\MarketingPaginatedApiService;
use App\Services\Marketing\MarketingRrhhColaboradoresApiService;
use App\Services\Marketing\MarketingTravelAgenciesApiService;
use App\Services\Marketing\MarketingTravelAgentsApiService;

readonly class BirthdayNotificationAudienceProfile
{
    /**
     * @param  class-string<MarketingPaginatedApiService>  $apiService
     * @param  list<string>  $birthDateFields
     * @param  list<string>  $emailFields
     * @param  list<string>  $nameFields
     */
    public function __construct(
        public BirthdayNotificationAudience $audience,
        public string $apiService,
        public array $birthDateFields,
        public array $emailFields,
        public array $nameFields,
    ) {}

    public static function for(BirthdayNotificationAudience $audience): ?self
    {
        return match ($audience) {
            BirthdayNotificationAudience::BrokerAgents => new self(
                audience: $audience,
                apiService: MarketingAgentsApiService::class,
                birthDateFields: ['birth_date'],
                emailFields: ['email'],
                nameFields: ['name_corporative', 'name'],
            ),
            BirthdayNotificationAudience::TravelAgents => new self(
                audience: $audience,
                apiService: MarketingTravelAgentsApiService::class,
                birthDateFields: ['fechaNacimiento'],
                emailFields: ['email'],
                nameFields: ['name'],
            ),
            BirthdayNotificationAudience::TravelAgencies => new self(
                audience: $audience,
                apiService: MarketingTravelAgenciesApiService::class,
                birthDateFields: ['FechaNacimientoRepresentante'],
                emailFields: ['email'],
                nameFields: ['representante', 'name'],
            ),
            BirthdayNotificationAudience::IndividualAffiliates => new self(
                audience: $audience,
                apiService: MarketingIndividualAffiliatesApiService::class,
                birthDateFields: ['birth_date'],
                emailFields: ['email'],
                nameFields: ['full_name', 'name'],
            ),
            BirthdayNotificationAudience::CorporateAffiliates => new self(
                audience: $audience,
                apiService: MarketingCorporateAffiliatesApiService::class,
                birthDateFields: ['birth_date'],
                emailFields: ['email'],
                nameFields: ['first_name', 'name'],
            ),
            BirthdayNotificationAudience::Collaborators => new self(
                audience: $audience,
                apiService: MarketingRrhhColaboradoresApiService::class,
                birthDateFields: ['fechaNacimiento'],
                emailFields: ['emailCorporativo', 'emailPersonal', 'emailAlternativo'],
                nameFields: ['fullName', 'nombre'],
            ),
            BirthdayNotificationAudience::BrokerAgencies,
            BirthdayNotificationAudience::Doctors,
            BirthdayNotificationAudience::NaturalSuppliers,
            BirthdayNotificationAudience::LegalSuppliers,
            BirthdayNotificationAudience::ClientGroups => null,
        };
    }
}
