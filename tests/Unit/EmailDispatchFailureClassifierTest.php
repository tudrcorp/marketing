<?php

use App\Marketing\EmailDispatchFailureKind;
use App\Services\Marketing\EmailDispatchFailureClassifier;
use Tests\TestCase;

// Los cooldowns se leen de config(), así que este archivo necesita la app iniciada.
uses(TestCase::class);

beforeEach(function () {
    $this->classifier = new EmailDispatchFailureClassifier;
});

test('classifies the gmail daily quota rejection', function () {
    $kind = $this->classifier->classify(502, 'Data command failed: 550-5.4.5 Daily user sending limit exceeded. For more information on Gmail sending limits go to https://support.google.com/a/answer/166852');

    expect($kind)->toBe(EmailDispatchFailureKind::QuotaExceeded)
        ->and($kind->shouldTripCircuit())->toBeTrue()
        ->and($kind->isRetryable())->toBeTrue();
});

test('classifies the login throttling that follows a quota rejection', function () {
    $kind = $this->classifier->classify(502, 'Invalid login: 454-4.7.0 Too many login attempts, please try again later.');

    expect($kind)->toBe(EmailDispatchFailureKind::AuthThrottled)
        ->and($kind->shouldTripCircuit())->toBeTrue();
});

test('a missing mailbox is permanent and never retried', function () {
    $kind = $this->classifier->classify(502, 'The email account that you tried to reach does not exist. 550 5.1.1');

    expect($kind)->toBe(EmailDispatchFailureKind::Permanent)
        ->and($kind->isRetryable())->toBeFalse()
        ->and($kind->shouldTripCircuit())->toBeFalse();
});

test('quota wins over the generic 550 branch', function () {
    // `550-5.4.5` contiene «550»: sin prioridad explícita se leería como buzón inexistente.
    expect($this->classifier->classify(502, '550-5.4.5 Daily user sending limit exceeded'))
        ->toBe(EmailDispatchFailureKind::QuotaExceeded);
});

test('connection resets and rate limits are transient', function (int $status, string $message) {
    expect($this->classifier->classify($status, $message))->toBe(EmailDispatchFailureKind::Transient);
})->with([
    [502, 'Error: read ECONNRESET'],
    [502, '421 4.7.0 Try again later'],
    [429, 'Too many requests'],
]);

test('reads the smtp code hidden inside the failures list', function () {
    // En un envío parcial el API responde 207 y el motivo real solo viaja dentro de
    // `failures[]`; el `reason` de primer nivel no menciona el código SMTP.
    $body = [
        'success' => false,
        'reason' => 'Se enviaron 30 de 50 correos',
        'failures' => [
            ['email' => 'uno@example.com', 'reason' => 'Data command failed: 550-5.4.5 Daily user sending limit exceeded'],
        ],
    ];

    $message = $this->classifier->messageFromResponseBody($body, 'Se enviaron 30 de 50 correos');

    expect($this->classifier->classify(207, $message))->toBe(EmailDispatchFailureKind::QuotaExceeded);
});

test('extracts the addresses that failed so the retry skips the ones already sent', function () {
    $addresses = $this->classifier->failedAddresses([
        'failures' => [
            ['email' => 'uno@example.com', 'reason' => 'x'],
            ['to' => 'dos@example.com', 'reason' => 'x'],
            ['address' => 'tres@example.com', 'reason' => 'x'],
            ['email' => 'uno@example.com', 'reason' => 'duplicado'],
            ['reason' => 'sin dirección'],
        ],
    ]);

    expect($addresses)->toBe(['uno@example.com', 'dos@example.com', 'tres@example.com']);
});

test('quota cooldown is long and transient cooldown grows with each attempt', function () {
    config()->set('services.marketing_api.mass_email_quota_cooldown_minutes', 60);

    expect(EmailDispatchFailureKind::QuotaExceeded->cooldownSeconds(1))->toBe(3600)
        ->and(EmailDispatchFailureKind::Transient->cooldownSeconds(1))->toBe(60)
        ->and(EmailDispatchFailureKind::Transient->cooldownSeconds(3))->toBe(240)
        ->and(EmailDispatchFailureKind::Transient->cooldownSeconds(9))->toBe(900);
});
