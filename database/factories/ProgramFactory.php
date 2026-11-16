<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Program '.fake()->unique()->words(3, true),
            'description' => fake()->sentence(14),
            'category' => 'Pelaksanaan Pembangunan Desa',
            'location' => 'Dusun I',
            'year' => 2027,
            'estimated_budget' => '25000000.00',
            'status' => 'proposed',
            'voting_open' => true,
            'voting_ends_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(['voting_open' => false]);
    }
}
