<?php

use App\Support\DatePresentation;

it('formats slash separated dates as day month year', function () {
    expect(DatePresentation::format('09/03/2023'))->toBe('09/03/2023')
        ->and(DatePresentation::format('13/12/1982'))->toBe('13/12/1982');
});

it('formats iso dates for display', function () {
    expect(DatePresentation::format('1988-04-23'))->toBe('23/04/1988');
});

it('returns null for invalid years', function () {
    expect(DatePresentation::format('0009-01-01'))->toBeNull();
});
