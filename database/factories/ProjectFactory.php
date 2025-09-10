<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');

        return [
            'name' => $this->faker->company . ' - ' . $this->faker->words(2, true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $this->faker->address,
            'latitude' => $this->faker->latitude(-18.5, -3), // Peru coordinates
            'longitude' => $this->faker->longitude(-81.3, -68.6), // Peru coordinates
            'quote_id' => null, // Will be set if needed in tests
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the project is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the project is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the project is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(60),
        ]);
    }
}
