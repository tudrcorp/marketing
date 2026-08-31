<?php

use App\Marketing\MarketingPermission;

test('permission groups include every marketing permission once', function () {
    $groupedPermissions = collect(MarketingPermission::groups())
        ->flatMap(fn (array $group): array => collect($group['permissions'])->pluck('key')->all())
        ->values()
        ->all();

    expect($groupedPermissions)->toHaveCount(count(MarketingPermission::all()))
        ->and(collect($groupedPermissions)->sort()->values()->all())
        ->toEqual(collect(MarketingPermission::all())->sort()->values()->all());
});

test('permission groups expose labels and descriptions for each permission', function () {
    $firstGroup = MarketingPermission::groups()[0];

    expect($firstGroup)
        ->toHaveKeys(['key', 'label', 'description', 'icon', 'permissions'])
        ->and($firstGroup['permissions'][0])
        ->toHaveKeys(['key', 'label', 'description']);
});
