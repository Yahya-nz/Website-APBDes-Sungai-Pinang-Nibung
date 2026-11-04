<?php

namespace Database\Factories;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Dataset> */
class DatasetFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->sentence(3), 'year' => 2026, 'source' => 'Dokumen pengujian', 'source_date' => '2026-11-03', 'is_demo' => false];
    }
}
