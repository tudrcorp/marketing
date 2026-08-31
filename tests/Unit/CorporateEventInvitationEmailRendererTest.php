<?php

use App\Mail\CorporateEventInvitationEmailRenderer;
use App\Marketing\CorporateEventModality;
use App\Marketing\CorporateEventType;
use App\Models\CorporateEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('corporate event invitation email renderer builds branded html with event details and cta', function () {
    $event = new CorporateEvent([
        'title' => 'Capacitación TDG',
        'event_type' => CorporateEventType::BusinessCapture->value,
        'modality' => CorporateEventModality::Virtual->value,
        'starts_at' => Carbon::parse('2026-06-11 00:00:00'),
        'venue_name' => 'Sede Central',
        'venue_address' => 'Av. Principal',
        'registration_url' => 'https://tdg-marketing.test/inscripcion/token-demo',
    ]);

    $html = app(CorporateEventInvitationEmailRenderer::class)->render(
        event: $event,
        message: 'Te invitamos a participar en esta capacitación.',
        sentByName: 'Analista TDG',
    );

    expect($html)
        ->toContain('<html')
        ->toContain('cid:company-logo')
        ->toContain('Capacitación TDG')
        ->toContain('Te invitamos a participar en esta capacitación.')
        ->toContain('Inscribirme ahora')
        ->toContain('https://tdg-marketing.test/inscripcion/token-demo')
        ->toContain('Actividad de captación')
        ->toContain('Virtual')
        ->toContain('Analista TDG')
        ->toContain('Tu Doctor Group');
});

test('corporate event invitation email renderer embeds cover image with cid when available', function () {
    Storage::fake('public');
    Storage::disk('public')->put('corporate-events/covers/demo.jpg', 'cover-image-bytes');

    $event = new CorporateEvent([
        'title' => 'Capacitación TDG',
        'event_type' => CorporateEventType::BusinessCapture->value,
        'modality' => CorporateEventModality::Virtual->value,
        'starts_at' => Carbon::parse('2026-06-11 00:00:00'),
        'cover_image' => 'corporate-events/covers/demo.jpg',
        'registration_url' => 'https://tdg-marketing.test/inscripcion/token-demo',
    ]);

    $html = app(CorporateEventInvitationEmailRenderer::class)->render(
        event: $event,
        message: 'Te invitamos.',
    );

    expect($html)->toContain('cid:campaign-image');
});
