<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Timesheet;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['present', 'absent', 'late', 'permission', 'sick_leave'];
        $status = $this->faker->randomElement($statuses);

        // Generate realistic times based on status
        $checkInDate = null;
        $checkOutDate = null;
        $breakDate = null;
        $endBreakDate = null;

        $baseDate = $this->faker->dateTimeBetween('-30 days', 'now');

        switch ($status) {
            case 'present':
                $checkInDate = $baseDate;
                $checkOutDate = $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 23:59:59');
                $breakDate = $this->faker->optional(0.8)->dateTimeBetween($checkInDate, $checkOutDate ?: $checkInDate->format('Y-m-d') . ' 23:59:59');
                if ($breakDate) {
                    $endBreakDate = $this->faker->dateTimeBetween($breakDate, $checkOutDate ?: $checkInDate->format('Y-m-d') . ' 23:59:59');
                }
                break;

            case 'late':
                $checkInDate = $baseDate;
                $checkOutDate = $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 23:59:59');
                $breakDate = $this->faker->optional(0.7)->dateTimeBetween($checkInDate, $checkOutDate ?: $checkInDate->format('Y-m-d') . ' 23:59:59');
                if ($breakDate) {
                    $endBreakDate = $this->faker->dateTimeBetween($breakDate, $checkOutDate ?: $checkInDate->format('Y-m-d') . ' 23:59:59');
                }
                break;

            case 'permission':
                $checkInDate = $baseDate;
                $checkOutDate = $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 18:00:00');
                break;
        }

        return [
            'timesheet_id' => Timesheet::factory(),
            'employee_id' => Employee::factory(),
            'shift' => $this->faker->randomElement(['day', 'night']),
            'check_in_date' => $checkInDate,
            'break_date' => $breakDate,
            'end_break_date' => $endBreakDate,
            'check_out_date' => $checkOutDate,
            'status' => $status,
            'observation' => $this->faker->optional(0.3)->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the attendance is present.
     */
    public function present(): static
    {
        return $this->state(function (array $attributes) {
            $checkInDate = $this->faker->dateTimeBetween('-7 days', 'now');
            $checkOutDate = $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 18:00:00');

            return [
                'status' => 'present',
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'shift' => 'day',
            ];
        });
    }

    /**
     * Indicate that the attendance is absent.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'absent',
            'check_in_date' => null,
            'check_out_date' => null,
            'break_date' => null,
            'end_break_date' => null,
            'observation' => 'No se presentó al trabajo',
        ]);
    }

    /**
     * Indicate that the attendance is late.
     */
    public function late(): static
    {
        return $this->state(function (array $attributes) {
            $checkInDate = $this->faker->dateTimeBetween('-7 days', 'now');
            $checkOutDate = $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 18:00:00');

            return [
                'status' => 'late',
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'observation' => 'Llegó tarde',
            ];
        });
    }

    /**
     * Indicate that the attendance is due to permission.
     */
    public function permission(): static
    {
        return $this->state(function (array $attributes) {
            $checkInDate = $this->faker->dateTimeBetween('-7 days', 'now');
            $checkOutDate = $this->faker->dateTimeBetween($checkInDate, $checkInDate->format('Y-m-d') . ' 15:00:00');

            return [
                'status' => 'permission',
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'observation' => 'Permiso autorizado',
            ];
        });
    }

    /**
     * Indicate that the attendance is due to sick leave.
     */
    public function sickLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sick_leave',
            'check_in_date' => null,
            'check_out_date' => null,
            'break_date' => null,
            'end_break_date' => null,
            'observation' => 'Licencia por enfermedad',
        ]);
    }
}
