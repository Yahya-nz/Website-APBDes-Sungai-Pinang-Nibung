<?php

namespace App\Support;

final class Money
{
    public static function cents(string|int|float $value): int
    {
        $decimal = is_float($value) ? number_format($value, 2, '.', '') : (string) $value;
        $negative = str_starts_with($decimal, '-');
        $parts = explode('.', ltrim($decimal, '-'));
        $cents = ((int) $parts[0] * 100) + (int) str_pad(substr($parts[1] ?? '', 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }

    public static function sum(iterable $values): float
    {
        $total = 0;
        foreach ($values as $value) {
            $total += self::cents($value);
        }

        return $total / 100;
    }

    /**
     * Ringkas nominal besar untuk kartu ringkasan, misalnya 1.800.000.000 menjadi "1,80" miliar.
     *
     * @return array{value: string, unit: string}
     */
    public static function short(float $amount): array
    {
        $absolute = abs($amount);

        if ($absolute >= 1_000_000_000) {
            return ['value' => number_format($amount / 1_000_000_000, 2, ',', '.'), 'unit' => 'miliar'];
        }

        if ($absolute >= 1_000_000) {
            $millions = $amount / 1_000_000;
            $decimals = fmod($millions, 1.0) === 0.0 ? 0 : 1;

            return ['value' => number_format($millions, $decimals, ',', '.'), 'unit' => 'juta'];
        }

        return ['value' => number_format($amount, 2, ',', '.'), 'unit' => ''];
    }
}
