<?php

namespace Database\Factories;

use App\Models\BudgetLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BudgetLine> */
class BudgetLineFactory extends Factory
{
    public function definition(): array
    {
        return ['year' => 2026, 'type' => 'income', 'category' => 'Pendapatan Asli Desa', 'description' => fake()->unique()->sentence(4), 'amount' => '1000000.00', 'source' => 'Dokumen pengujian', 'source_date' => '2026-11-03', 'verification_status' => 'unverified', 'is_demo' => false];
    }

    /**
     * Baris yang sudah dicocokkan dengan dokumen sumber, sehingga realisasinya ikut dihitung.
     */
    public function verified(): static
    {
        return $this->state(['verification_status' => 'verified']);
    }
}
