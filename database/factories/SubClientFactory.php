<?php

namespace Database\Factories;

use App\Models\SubClient;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubClient>
 */
class SubClientFactory extends Factory
{
    protected $model = SubClient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => $this->faker->unique()->regexify('[A-Z]{3}-[A-Z]{3}-[A-Z]{2}\/[A-Z]{2}-[0-9]{4}-[0-9]'),
            'description' => $this->faker->sentence(5),
            'location' => $this->faker->address(),
            'latitude' => $this->faker->latitude(-18, -1), // Peru coordinates range
            'longitude' => $this->faker->longitude(-81, -68), // Peru coordinates range
        ];
    }
}
