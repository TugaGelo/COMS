<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Concourse;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requirements_status = $this->faker->randomElement(['pending', 'approved', 'rejected']);
        
        // If requirements are rejected, application can only be pending or rejected
        $application_status_options = $requirements_status === 'rejected' 
            ? ['pending', 'rejected']
            : ['pending', 'approved', 'rejected'];

        return [
            'user_id' => User::factory(),
            'concourse_id' => Concourse::factory(),
            'space_id' => Space::factory(),
            'requirements_status' => $requirements_status,
            'application_status' => $this->faker->randomElement($application_status_options),
            'business_name' => $this->faker->company,
            'owner_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'business_type' => $this->faker->randomElement(['Restaurant', 'Retail', 'Service', 'Office']),
            'concourse_lease_term' => $this->faker->numberBetween(1, 10),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
