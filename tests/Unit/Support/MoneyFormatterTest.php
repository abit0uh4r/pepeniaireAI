<?php

use App\Support\MoneyFormatter;

test('money formatter renders decimal amounts in Moroccan dirhams without floats', function (string $amount, string $expected) {
    expect(MoneyFormatter::formatMad($amount))->toBe($expected);
})->with([
    ['9.90', '9,90 MAD'],
    ['39.00', '39,00 MAD'],
    ['1234.5', '1 234,50 MAD'],
    ['0.00', '0,00 MAD'],
]);
