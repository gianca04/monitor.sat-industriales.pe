<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase, CreatesAuthenticatedUser;

    protected $user;
    protected $token;
    protected $project;
    protected $timesheet;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $auth = $this->createAuthenticatedUser();
        $this->user = $auth['user'];
        $this->token = $auth['token'];

        $this->project = Project::factory()->create();
        $this->employee = Employee::factory()->create();
        $this->timesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id,
            'check_in_date' => now(),
        ]);
    }

    protected function authenticatedJson($method, $uri, array $data = [], array $headers = [])
    {
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->token
        ], $headers);

        return $this->json($method, $uri, $data, $headers);
    }

    protected function authenticatedGetJson($uri, array $headers = [])
    {
        return $this->authenticatedJson('GET', $uri, [], $headers);
    }

    protected function authenticatedPostJson($uri, array $data = [], array $headers = [])
    {
        return $this->authenticatedJson('POST', $uri, $data, $headers);
    }

    protected function authenticatedPutJson($uri, array $data = [], array $headers = [])
    {
        return $this->authenticatedJson('PUT', $uri, $data, $headers);
    }

    protected function authenticatedDeleteJson($uri, array $data = [], array $headers = [])
    {
        return $this->authenticatedJson('DELETE', $uri, $data, $headers);
    }

    /** @test */
    public function it_can_list_attendances()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->authenticatedGetJson('/api/attendances');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencias obtenidas correctamente'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'timesheet_id',
                        'employee_id',
                        'status',
                        'check_in_date',
                        'check_out_date',
                        'break_date',
                        'observation',
                        'created_at',
                        'updated_at',
                        'employee',
                        'timesheet'
                    ]
                ],
                'message'
            ]);
    }

    /** @test */
    public function it_can_filter_attendances_by_timesheet()
    {
        $otherTimesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id,
            'check_in_date' => now()->addDays(1),
        ]);

        $attendance1 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $attendance2 = Attendance::factory()->create([
            'timesheet_id' => $otherTimesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->authenticatedGetJson("/api/attendances?timesheet_id={$this->timesheet->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $attendance1->id);
    }

    /** @test */
    public function it_can_filter_attendances_by_employee()
    {
        $otherEmployee = Employee::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $attendance2 = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->authenticatedGetJson("/api/attendances?employee_id={$this->employee->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $attendance1->id);
    }

    /** @test */
    public function it_can_filter_attendances_by_status()
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

        $response = $this->authenticatedGetJson('/api/attendances?status=present');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'present');
    }

    /** @test */
    public function it_can_show_specific_attendance()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->authenticatedGetJson("/api/attendances/{$attendance->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencia obtenida correctamente'
            ])
            ->assertJsonPath('data.id', $attendance->id);
    }

    /** @test */
    public function it_returns_error_when_attendance_not_found()
    {
        $response = $this->authenticatedGetJson('/api/attendances/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Asistencia no encontrada'
            ]);
    }

    /** @test */
    public function it_can_create_attendance()
    {
        $attendanceData = [
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
            'check_in_date' => now(),
            'check_out_date' => now()->addHours(8),
            'shift' => 'day', // Corregir: usar valores válidos
        ];

        $response = $this->authenticatedPostJson('/api/attendances', $attendanceData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencia registrada correctamente'
            ]);

        $this->assertDatabaseHas('attendances', [
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_attendance()
    {
        $response = $this->authenticatedPostJson('/api/attendances', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timesheet_id', 'employee_id', 'status']);
    }

    /** @test */
    public function it_validates_timesheet_exists()
    {
        $attendanceData = [
            'timesheet_id' => 999,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ];

        $response = $this->authenticatedPostJson('/api/attendances', $attendanceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timesheet_id']);
    }

    /** @test */
    public function it_validates_employee_exists()
    {
        $attendanceData = [
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => 999,
            'status' => 'present',
        ];

        $response = $this->authenticatedPostJson('/api/attendances', $attendanceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id']);
    }

    /** @test */
    public function it_validates_status_values()
    {
        $attendanceData = [
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'invalid_status',
        ];

        $response = $this->authenticatedPostJson('/api/attendances', $attendanceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_prevents_duplicate_attendance_for_same_employee_and_timesheet()
    {
        // Crear primera asistencia
        Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        // Intentar crear duplicado
        $attendanceData = [
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ];

        $response = $this->authenticatedPostJson('/api/attendances', $attendanceData);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Ya existe una asistencia registrada para este empleado en este timesheet');
    }

    /** @test */
    public function it_can_update_attendance()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $updateData = [
            'status' => 'late',
            'observation' => 'Llegó tarde',
        ];

        $response = $this->authenticatedPutJson("/api/attendances/{$attendance->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencia actualizada correctamente'
            ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'late',
            'observation' => 'Llegó tarde',
        ]);
    }

    /** @test */
    public function it_can_delete_attendance()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->authenticatedDeleteJson("/api/attendances/{$attendance->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencia eliminada correctamente'
            ]);

        $this->assertSoftDeleted('attendances', ['id' => $attendance->id]);
    }

    /** @test */
    public function it_can_restore_soft_deleted_attendance()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
        ]);

        $attendance->delete();

        $response = $this->authenticatedPostJson("/api/attendances/{$attendance->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencia restaurada correctamente'
            ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_can_create_bulk_attendances()
    {
        $employee2 = Employee::factory()->create();
        $employee3 = Employee::factory()->create();

        $bulkData = [
            'timesheet_id' => $this->timesheet->id, // Agregar timesheet_id requerido
            'attendances' => [
                [
                    'timesheet_id' => $this->timesheet->id,
                    'employee_id' => $this->employee->id,
                    'status' => 'present',
                    'shift' => 'day', // Corregir valores de shift
                ],
                [
                    'timesheet_id' => $this->timesheet->id,
                    'employee_id' => $employee2->id,
                    'status' => 'late',
                    'shift' => 'day', // Corregir valores de shift
                ],
                [
                    'timesheet_id' => $this->timesheet->id,
                    'employee_id' => $employee3->id,
                    'status' => 'absent',
                    'shift' => 'night', // Corregir valores de shift
                ]
            ]
        ];

        $response = $this->authenticatedPostJson('/api/attendances/bulk', $bulkData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Asistencias creadas en lote correctamente'
            ]);

        $this->assertDatabaseCount('attendances', 3);
    }

    /** @test */
    public function it_can_search_attendances()
    {
        $attendance = Attendance::factory()->create([
            'timesheet_id' => $this->timesheet->id,
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);

        $response = $this->authenticatedGetJson('/api/attendances/search?timesheet_id=' . $this->timesheet->id . '&status=present');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Búsqueda de asistencias completada' // Corregir mensaje
            ])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        // Create a new test instance without authentication
        $this->refreshApplication();

        $endpoints = [
            ['GET', '/api/attendances'],
            ['GET', '/api/attendances/1'],
            ['POST', '/api/attendances'],
            ['PUT', '/api/attendances/1'],
            ['DELETE', '/api/attendances/1'],
            ['POST', '/api/attendances/1/restore'],
            ['POST', '/api/attendances/bulk'],
            ['GET', '/api/attendances/search'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url, []);
            $response->assertStatus(401);
        }
    }
}
