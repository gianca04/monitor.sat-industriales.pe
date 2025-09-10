<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Client;
use App\Models\Employee;
use App\Models\SubClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'employee_id' => Employee::factory(),
            'sub_client_id' => SubClient::factory(),
            'TDR' => $this->faker->sentence(3),
            'quote_file' => $this->faker->optional()->word() . '.pdf',
            'correlative' => $this->faker->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'contractor' => $this->faker->company(),
            'pe_pt' => $this->faker->randomElement(['PE', 'PT']),
            'project_description' => $this->faker->sentence(10),
            'location' => $this->faker->city(),
            'delivery_term' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'status' => $this->faker->randomElement(['unassigned', 'in_progress', 'under_review', 'sent', 'rejected', 'accepted']),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the quote is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Indicate that the quote is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the quote is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
