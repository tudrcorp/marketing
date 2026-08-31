<?php

use App\Services\Marketing\MassEmailDispatchPace;
use Tests\TestCase;

uses(TestCase::class);

test('warns about gmail pacing and not relaunching without inventing a duration', function () {
    config()->set('services.marketing_api.mass_email_batch_size', 15);
    config()->set('services.marketing_api.mass_email_batch_pause_seconds', 30);

    expect(MassEmailDispatchPace::analystWarning())
        ->toContain('Los correos salen de a 15')
        ->toContain('30 s entre lotes')
        ->toContain('no relances')
        ->not->toContain('destinatarios');
});

test('estimates duration from recipient count including smtp pacing', function () {
    config()->set('services.marketing_api.mass_email_batch_size', 15);
    config()->set('services.marketing_api.mass_email_batch_pause_seconds', 30);

    // 45 dest = 3 lotes: 60 s entre lotes + ~90 s de SMTP de a uno ≈ 3 min.
    expect(MassEmailDispatchPace::analystWarning(45))
        ->toContain('~45 destinatarios')
        ->toContain('unos 3 min');
});
