<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Carbon\Carbon;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $timesheet;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->employee = Employee::factory()->create();

        $this->timesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'supervisor_id' => $this->user->id,
            'check_in_date' => Carbon::today()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_generate_attendance_report()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reporte de asistencias generado correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'attendances',
                    'statistics' => [
                        'total_attendances',
                        'by_status',
                        'by_project'
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_validates_required_date_fields_for_attendance_report()
    {
        $response = $this->getJson('/api/reports/attendance');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function it_validates_date_range_for_attendance_report()
    {
        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'start_date' => '2023-12-01',
            'end_date' => '2023-01-01',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_can_filter_attendance_report_by_project()
    {
        $otherProject = Project::factory()->create();
        $otherTimesheet = Timesheet::factory()->create([
            'project_id' => $otherProject->id,
            'supervisor_id' => $this->user->id,
            'check_in_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $attendance1 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $attendance2 = Attendance::factory()->create([
            'timesheet_id' => $otherTimesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'project_id' => $this->project->id,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.attendances');
    }

    /** @test */
    public function it_can_filter_attendance_report_by_status()
    {
        $attendance1 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $attendance2 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => Employee::factory()->create()->id,
            'status' => 'absent',
        ]);

        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'status' => 'present',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.attendances')
            ->assertJsonPath('data.attendances.0.status', 'present');
    }

    /** @test */
    public function it_can_generate_project_report()
    {
        $timesheet2 = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'supervisor_id' => $this->user->id,
            'check_in_date' => Carbon::yesterday()->format('Y-m-d'),
        ]);

        $response = $this->getJson('/api/reports/project?' . http_build_query([
            'project_id' => $this->project->id,
            'start_date' => Carbon::yesterday()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reporte de proyecto generado correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'project',
                    'timesheets',
                    'statistics' => [
                        'total_timesheets',
                        'total_attendances',
                        'attendance_rate',
                        'by_status'
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_project_report()
    {
        $response = $this->getJson('/api/reports/project');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_id', 'start_date', 'end_date']);
    }

    /** @test */
    public function it_validates_project_exists_for_project_report()
    {
        $response = $this->getJson('/api/reports/project?' . http_build_query([
            'project_id' => 999,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    /** @test */
    public function it_can_generate_employee_report()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/reports/employee?' . http_build_query([
            'employee_id' => $this->employee->id,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reporte de empleado generado correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'employee',
                    'attendances',
                    'statistics' => [
                        'total_attendances',
                        'by_status',
                        'attendance_rate',
                        'total_worked_hours'
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_employee_report()
    {
        $response = $this->getJson('/api/reports/employee');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id', 'start_date', 'end_date']);
    }

    /** @test */
    public function it_validates_employee_exists_for_employee_report()
    {
        $response = $this->getJson('/api/reports/employee?' . http_build_query([
            'employee_id' => 999,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id']);
    }

    /** @test */
    public function it_can_generate_summary_report()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/reports/summary?' . http_build_query([
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reporte resumen generado correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'statistics' => [
                        'total_projects',
                        'total_timesheets',
                        'total_attendances',
                        'total_employees',
                        'attendance_rate',
                        'by_project',
                        'by_status'
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_summary_report()
    {
        $response = $this->getJson('/api/reports/summary');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function it_calculates_correct_statistics_in_attendance_report()
    {
        $employee2 = Employee::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $attendance2 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $employee2->id,
            'status' => 'absent',
        ]);

        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.statistics.total_attendances', 2)
            ->assertJsonPath('data.statistics.by_status.present', 1)
            ->assertJsonPath('data.statistics.by_status.absent', 1);
    }

    /** @test */
    public function it_handles_empty_date_ranges_gracefully()
    {
        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'start_date' => Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.statistics.total_attendances', 0);
    }

    /** @test */
    public function it_can_generate_daily_summary()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $response = $this->getJson('/api/reports/daily-summary?' . http_build_query([
            'date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resumen diario generado correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'date',
                    'statistics' => [
                        'total_timesheets',
                        'total_attendances',
                        'by_status',
                        'by_project'
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_validates_date_field_for_daily_summary()
    {
        $response = $this->getJson('/api/reports/daily-summary');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /** @test */
    public function it_validates_status_values_in_attendance_report()
    {
        $response = $this->getJson('/api/reports/attendance?' . http_build_query([
            'status' => 'invalid_status',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        // Create a new test instance without authentication
        $this->refreshApplication();

        $endpoints = [
            ['GET', '/api/reports/attendance?start_date=2023-01-01&end_date=2023-01-31'],
            ['GET', '/api/reports/project?project_id=1&start_date=2023-01-01&end_date=2023-01-31'],
            ['GET', '/api/reports/employee?employee_id=1&start_date=2023-01-01&end_date=2023-01-31'],
            ['GET', '/api/reports/summary?start_date=2023-01-01&end_date=2023-01-31'],
            ['GET', '/api/reports/daily-summary?date=2023-01-01'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url, []);
            $response->assertStatus(401);
        }
    }
}
