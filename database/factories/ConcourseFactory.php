<?php

namespace Database\Factories;

use App\Models\Concourse;
use App\Models\ConcourseRate;
use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Concourse>
 */
class ConcourseFactory extends Factory
{
    protected $model = Concourse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'rate_id' => ConcourseRate::factory(),
            'lease_term' => $this->faker->numberBetween(1, 120), // Lease term in months (1 to 10 years)
            'spaces' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
