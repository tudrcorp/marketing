<?php

use App\Filament\Resources\BrokerAgencies\Support\BrokerAgencyPresentation;

it('maps agency type ids to their labels', function () {
    expect(BrokerAgencyPresentation::agencyTypeLabel('1'))->toBe('MASTER')
        ->and(BrokerAgencyPresentation::agencyTypeLabel('3'))->toBe('GENERAL')
        ->and(BrokerAgencyPresentation::agencyTypeLabel('2'))->toBe('Corredor');
});
