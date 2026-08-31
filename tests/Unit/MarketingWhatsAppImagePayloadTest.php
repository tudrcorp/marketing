<?php

use App\Services\Marketing\MarketingPhoneNormalizer;
use App\Services\Marketing\MarketingWhatsAppImagePayload;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

test('whatsapp image payload uses base64 for local development urls', function () {
    Storage::fake('public');
    Storage::disk('public')->put('birthday-notifications/images/sample.png', 'image-bytes');

    config(['app.url' => 'https://tdg-marketing.test']);

    $payload = MarketingWhatsAppImagePayload::fromStoragePath('birthday-notifications/images/sample.png');

    expect($payload)
        ->toHaveKey('image_base64')
        ->and($payload['image_base64'])->toStartWith('data:image/png;base64,');
});

test('whatsapp image payload keeps public url for production hosts', function () {
    expect(MarketingWhatsAppImagePayload::isPubliclyAccessibleUrl('https://marketing.tudoctorgroup.com/storage/sample.png'))->toBeTrue()
        ->and(MarketingWhatsAppImagePayload::isPubliclyAccessibleUrl('https://tdg-marketing.test/storage/sample.png'))->toBeFalse()
        ->and(MarketingWhatsAppImagePayload::isPubliclyAccessibleUrl('http://localhost/storage/sample.png'))->toBeFalse();
});

test('phone normalizer converts venezuelan local mobile numbers', function () {
    expect(MarketingPhoneNormalizer::normalize('04141234567'))->toBe('584141234567')
        ->and(MarketingPhoneNormalizer::normalize('+58 414 123 4567'))->toBe('584141234567');
});
