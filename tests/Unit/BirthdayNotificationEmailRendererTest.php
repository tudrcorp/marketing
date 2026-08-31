<?php

use App\Mail\BirthdayNotificationEmailRenderer;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);
use App\Models\BirthdayNotification;

test('birthday notification email renderer builds branded html with test banner', function () {
    $notification = new BirthdayNotification([
        'name' => 'Cumpleaños TDG',
        'copy' => '¡Feliz cumpleaños!',
        'image' => null,
    ]);

    $html = app(BirthdayNotificationEmailRenderer::class)->render(
        notification: $notification,
        isTest: true,
        sentByName: 'Gustavo Camacho',
    );

    expect($html)
        ->toContain('<html')
        ->toContain('cid:company-logo')
        ->toContain('¡Feliz cumpleaños!')
        ->toContain('text-align:center')
        ->not->toContain('Notificación de cumpleaños')
        ->not->toContain('<h1')
        ->toContain('Envío de prueba.')
        ->toContain('Gustavo Camacho')
        ->toContain('Tu Doctor Group');
});

test('birthday notification email renderer embeds campaign image with cid when available', function () {
    $notification = new BirthdayNotification([
        'name' => 'Cumpleaños con imagen',
        'copy' => 'Mensaje con imagen',
        'image' => 'birthday-notifications/sample.jpg',
    ]);

    $html = app(BirthdayNotificationEmailRenderer::class)->render(
        notification: $notification,
        isTest: false,
    );

    expect($html)->not->toContain('cid:campaign-image');
});

test('birthday notification email renderer uses cid when image exists on disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('birthday-notifications/sample.jpg', 'image-bytes');

    $notification = new BirthdayNotification([
        'name' => 'Cumpleaños con imagen',
        'copy' => 'Mensaje con imagen',
        'image' => 'birthday-notifications/sample.jpg',
    ]);

    $html = app(BirthdayNotificationEmailRenderer::class)->render(
        notification: $notification,
        isTest: false,
    );

    expect($html)->toContain('cid:campaign-image');
});
