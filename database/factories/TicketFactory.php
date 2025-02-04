<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_ticket_number' => fake()->unique()->numberBetween(1000, 9999),
            'created_by' => fake()->numberBetween(1, 10),
            'assigned_to' => fake()->numberBetween(1, 10),
            'space_id' => fake()->numberBetween(1, 10),
            'concourse_id' => fake()->numberBetween(1, 10),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'concern_type' => fake()->randomElement(['maintenance and repair', 'safety and security', 'cleanliness and sanitation', 'lease and contractual', 'utilities concerns', 'aesthetic and comestics', 'general support', 'others']),
            'remarks' => fake()->paragraph(),
            'status' => fake()->randomElement(['open', 'closed']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
        ];
    }
}
