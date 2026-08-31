<?php

use App\Support\NamePresentation;

it('formats names in uppercase', function () {
    expect(NamePresentation::uppercase('  adeliris valentín '))->toBe('ADELIRIS VALENTÍN');
});

it('returns null for blank names', function () {
    expect(NamePresentation::uppercase(null))->toBeNull()
        ->and(NamePresentation::uppercase('   '))->toBeNull();
});
