<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{float, string, string}>
     */
    public static function shortAmounts(): array
    {
        return [
            'miliar' => [1_800_000_000, '1,80', 'miliar'],
            'juta bulat' => [450_000_000, '450', 'juta'],
            'juta pecahan' => [12_500_000, '12,5', 'juta'],
            'di bawah satu juta' => [750_000, '750.000,00', ''],
            'nol tetap terlihat' => [0, '0,00', ''],
        ];
    }

    #[DataProvider('shortAmounts')]
    public function test_short_amount_uses_indonesian_units(float $amount, string $value, string $unit): void
    {
        $this->assertSame(['value' => $value, 'unit' => $unit], Money::short($amount));
    }

    public function test_sum_adds_values_in_cents_without_float_drift(): void
    {
        $this->assertSame(0.3, Money::sum(['0.10', '0.20']));
    }
}
