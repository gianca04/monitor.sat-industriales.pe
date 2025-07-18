<?php

namespace Database\Factories;

use App\Models\Timesheet;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timesheet>
 */
class TimesheetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Timesheet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkInDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $breakDate = $this->faker->optional(0.8)->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 23:59:59');

        return [
            'project_id' => Project::factory(),
            'employee_id' => \App\Models\Employee::factory(),
            'shift' => $this->faker->randomElement(['day', 'night']),
            'check_in_date' => $checkInDate,
            'break_date' => $breakDate,
            'end_break_date' => $breakDate ? $this->faker->dateTimeBetween($breakDate, $checkInDate->format('Y-m-d') . ' 23:59:59') : null,
            'check_out_date' => $this->faker->optional(0.7)->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 23:59:59'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the timesheet is for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_date' => now(),
        ]);
    }

    /**
     * Indicate that the timesheet is for yesterday.
     */
    public function yesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_date' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the timesheet is for day shift.
     */
    public function dayShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift' => 'day',
        ]);
    }

    /**
     * Indicate that the timesheet is for night shift.
     */
    public function nightShift(): static
    {
        return $this->state(fn (array $attributes) => [
            'shift' => 'night',
        ]);
    }

    /**
     * Indicate that the timesheet is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $checkInDate = $this->faker->dateTimeBetween('-7 days', 'now');
            return [
                'check_in_date' => $checkInDate,
                'check_out_date' => $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 18:00:00'),
            ];
        });
    }
}
