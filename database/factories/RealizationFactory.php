<?php

namespace Database\Factories;

use App\Models\BudgetLine;
use App\Models\Realization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Realization>
 */
class RealizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_line_id' => BudgetLine::factory()->state(['type' => 'expense']),
            'spent_on' => '2026-03-15',
            'amount' => '100000.00',
            'description' => 'Pembayaran '.fake()->words(3, true),
            'is_demo' => false,
        ];
    }

    public function demo(): static
    {
        return $this->state(['is_demo' => true]);
    }
}
