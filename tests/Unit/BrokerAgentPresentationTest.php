<?php

use App\Filament\Resources\BrokerAgents\Support\BrokerAgentPresentation;

it('formats iso birth dates for display', function () {
    expect(BrokerAgentPresentation::formatBirthDate('1981-01-18'))->toBe('18/01/1981');
});

it('returns null for blank birth dates', function () {
    expect(BrokerAgentPresentation::formatBirthDate(null))->toBeNull()
        ->and(BrokerAgentPresentation::formatBirthDate(''))->toBeNull();
});
