<?php

declare(strict_types=1);

namespace App\Support;

final class MoneyFormatter
{
    public static function formatMad(string|int $amount): string
    {
        $value = trim((string) $amount);
        $negative = str_starts_with($value, '-');
        $value = ltrim($negative ? substr($value, 1) : $value, '+');

        [$whole, $decimal] = array_pad(explode('.', $value, 2), 2, '0');
        $whole = ltrim($whole, '0') ?: '0';
        $decimal = str_pad(substr($decimal, 0, 2), 2, '0');
        $groupedWhole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $whole);

        return sprintf(
            '%s%s,%s MAD',
            $negative && $whole !== '0' ? '-' : '',
            $groupedWhole,
            $decimal,
        );
    }
}
