<?php

use App\Mail\MassNotificationEmailRenderer;
use App\Models\MassNotification;
use Tests\TestCase;

uses(TestCase::class);

test('mass notification email footer uses commercial department and is centered', function () {
    $notification = new MassNotification([
        'title' => 'Seminario TDG',
        'copy' => 'Del primer contacto al cierre.',
        'attachment' => null,
    ]);

    $html = app(MassNotificationEmailRenderer::class)->render(
        notification: $notification,
        sentByName: 'Gustavo Camacho',
    );

    expect($html)
        ->toContain('text-align:center')
        ->toContain('Mensaje enviado por <strong>Tu Doctor Group</strong>.')
        ->toContain('Campaña preparada por <strong>el Departamento Comercial</strong>.')
        ->toContain('*|UNSUB|*')
        ->toContain('*|LIST:ADDRESS|*')
        ->not->toContain('cid:company-logo')
        ->not->toContain('Gustavo Camacho');
});

test('transactional test render embeds the logo and skips mailchimp merge tags', function () {
    $notification = new MassNotification([
        'title' => 'Seminario TDG',
        'copy' => 'Del primer contacto al cierre.',
        'attachment' => null,
    ]);

    $html = app(MassNotificationEmailRenderer::class)->render(
        notification: $notification,
        sentByName: 'Gustavo Camacho',
        forTransactional: true,
    );

    expect($html)
        ->toContain('cid:company-logo')
        ->toContain('Tu Doctor Group LLC, Centro Lido 12, Caracas, Venezuela.')
        ->toContain('Este es un envío de prueba.')
        ->not->toContain('*|UNSUB|*')
        ->not->toContain('*|LIST:ADDRESS|*');
});
