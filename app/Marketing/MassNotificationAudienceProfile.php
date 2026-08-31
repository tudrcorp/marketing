<?php

namespace App\Marketing;

use App\Services\Marketing\MarketingAgenciesApiService;
use App\Services\Marketing\MarketingAgentsApiService;
use App\Services\Marketing\MarketingCorporateAffiliatesApiService;
use App\Services\Marketing\MarketingIndividualAffiliatesApiService;
use App\Services\Marketing\MarketingLegalSuppliersApiService;
use App\Services\Marketing\MarketingNaturalSuppliersApiService;
use App\Services\Marketing\MarketingPaginatedApiService;
use App\Services\Marketing\MarketingRrhhColaboradoresApiService;
use App\Services\Marketing\MarketingTravelAgenciesApiService;
use App\Services\Marketing\MarketingTravelAgentsApiService;

readonly class MassNotificationAudienceProfile
{
    /**
     * @param  class-string<MarketingPaginatedApiService>  $apiService
     * @param  list<string>  $emailFields
     * @param  list<string>  $phoneFields
     * @param  list<string>  $nameFields
     */
    public function __construct(
        public BirthdayNotificationAudience $audience,
        public string $apiService,
        public array $emailFields,
        public array $phoneFields,
        public array $nameFields,
    ) {}

    public static function for(BirthdayNotificationAudience $audience): self
    {
        return match ($audience) {
            BirthdayNotificationAudience::BrokerAgents => new self(
                audience: $audience,
                apiService: MarketingAgentsApiService::class,
                emailFields: ['email'],
                phoneFields: ['phone'],
                nameFields: ['name_corporative', 'name'],
            ),
            BirthdayNotificationAudience::BrokerAgencies => new self(
                audience: $audience,
                apiService: MarketingAgenciesApiService::class,
                emailFields: ['email'],
                phoneFields: ['phone'],
                nameFields: ['name_corporative', 'name'],
            ),
            BirthdayNotificationAudience::TravelAgents => new self(
                audience: $audience,
                apiService: MarketingTravelAgentsApiService::class,
                emailFields: ['email'],
                phoneFields: ['phone'],
                nameFields: ['name'],
            ),
            BirthdayNotificationAudience::TravelAgencies => new self(
                audience: $audience,
                apiService: MarketingTravelAgenciesApiService::class,
                emailFields: ['email'],
                phoneFields: ['phone', 'phoneAdditional'],
                nameFields: ['representante', 'name'],
            ),
            BirthdayNotificationAudience::IndividualAffiliates => new self(
                audience: $audience,
                apiService: MarketingIndividualAffiliatesApiService::class,
                emailFields: ['email'],
                phoneFields: ['phone'],
                nameFields: ['full_name', 'name'],
            ),
            BirthdayNotificationAudience::CorporateAffiliates => new self(
                audience: $audience,
                apiService: MarketingCorporateAffiliatesApiService::class,
                emailFields: ['email'],
                phoneFields: ['phone'],
                nameFields: ['first_name', 'name'],
            ),
            BirthdayNotificationAudience::Collaborators => new self(
                audience: $audience,
                apiService: MarketingRrhhColaboradoresApiService::class,
                emailFields: ['emailCorporativo', 'emailPersonal', 'emailAlternativo'],
                phoneFields: ['telefonoCorporativo', 'telefono'],
                nameFields: ['fullName', 'nombre'],
            ),
            BirthdayNotificationAudience::NaturalSuppliers => new self(
                audience: $audience,
                apiService: MarketingNaturalSuppliersApiService::class,
                emailFields: ['correo_principal', 'email'],
                phoneFields: ['personal_phone', 'local_phone'],
                nameFields: ['name', 'razon_social'],
            ),
            BirthdayNotificationAudience::LegalSuppliers => new self(
                audience: $audience,
                apiService: MarketingLegalSuppliersApiService::class,
                emailFields: ['correo_principal', 'email'],
                phoneFields: ['local_phone', 'personal_phone'],
                nameFields: ['name', 'razon_social'],
            ),
            BirthdayNotificationAudience::Doctors => new self(
                audience: $audience,
                apiService: MarketingNaturalSuppliersApiService::class,
                emailFields: ['correo_principal', 'email'],
                phoneFields: ['personal_phone', 'local_phone'],
                nameFields: ['name', 'razon_social'],
            ),
            BirthdayNotificationAudience::ClientGroups => throw new \LogicException('Los grupos de clientes se resuelven con ClientGroupContactCollector.'),
        };
    }
}
