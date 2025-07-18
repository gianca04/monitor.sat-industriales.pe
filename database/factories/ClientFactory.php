<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type' => $this->faker->randomElement(['RUC', 'DNI']),
            'document_number' => $this->faker->unique()->numerify('###########'),
            'person_type' => $this->faker->randomElement(['natural', 'juridica']),
            'business_name' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->address(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->unique()->safeEmail(),
            'logo' => null,
        ];
    }
}
