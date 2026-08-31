<?php

use App\Marketing\SocialPlatform;
use Tests\TestCase;

uses(TestCase::class);

test('social platform list only includes supported networks', function () {
    expect(collect(SocialPlatform::cases())->pluck('value')->all())
        ->toBe(['instagram', 'youtube', 'facebook', 'tiktok', 'x']);
});

test('social platform options include branded icons', function () {
    foreach (SocialPlatform::orderedCases() as $platform) {
        expect($platform->getImageUrl())
            ->toContain('images/icon-red-social/')
            ->and($platform->getSelectOptionHtml())
            ->toContain('<img')
            ->toContain($platform->getLabel());
    }

    expect(SocialPlatform::X->getLabel())->toBe('X')
        ->and(SocialPlatform::X->getImageUrl())->toContain('gorjeo.png');
});
