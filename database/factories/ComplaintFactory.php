<?php

namespace Database\Factories;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket' => Complaint::newTicket(),
            'name' => fake()->name(),
            'contact' => null,
            'topic' => 'question',
            'message' => fake()->sentence(12),
            'status' => 'new',
        ];
    }

    public function resolved(): static
    {
        return $this->state(['status' => 'resolved', 'response' => 'Terima kasih, sudah kami tindak lanjuti.', 'responded_at' => now()]);
    }
}
