<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = ['DNI', 'PASAPORTE', 'CARNET DE EXTRANJERIA'];

        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'document_type' => $this->faker->randomElement($documentTypes),
            'document_number' => $this->faker->unique()->numerify('########'),
            'address' => substr($this->faker->streetAddress, 0, 35), // Máximo 40 caracteres
            'date_birth' => $this->faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'date_contract' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'sex' => $this->faker->randomElement(['male', 'female']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the employee has a DNI document.
     */
    public function withDni(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'DNI',
            'document_number' => $this->faker->numerify('########'),
        ]);
    }

    /**
     * Indicate that the employee has a passport document.
     */
    public function withPassport(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'PASAPORTE',
            'document_number' => $this->faker->bothify('??######'),
        ]);
    }

    /**
     * Indicate that the employee is male.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'sex' => 'male',
        ]);
    }

    /**
     * Indicate that the employee is female.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'sex' => 'female',
        ]);
    }
}
