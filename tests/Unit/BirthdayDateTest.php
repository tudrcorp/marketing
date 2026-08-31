<?php

use App\Support\BirthdayDate;
use Carbon\Carbon;

test('birthday date matches iso dates against reference day', function () {
    $reference = Carbon::create(2026, 4, 23);

    expect(BirthdayDate::isBirthdayToday('1988-04-23', $reference))->toBeTrue()
        ->and(BirthdayDate::isBirthdayToday('1988-04-22', $reference))->toBeFalse();
});

test('birthday date matches slash separated dates against reference day', function () {
    $reference = Carbon::create(2026, 12, 13);

    expect(BirthdayDate::isBirthdayToday('13/12/1982', $reference))->toBeTrue()
        ->and(BirthdayDate::isBirthdayToday('12/13/1982', $reference))->toBeFalse();
});

test('birthday date rejects invalid or empty values', function () {
    expect(BirthdayDate::isBirthdayToday(null))->toBeFalse()
        ->and(BirthdayDate::isBirthdayToday(''))->toBeFalse()
        ->and(BirthdayDate::isBirthdayToday('invalid-date'))->toBeFalse();
});
