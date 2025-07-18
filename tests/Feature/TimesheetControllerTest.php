<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\Quote;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class TimesheetControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $project;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;

        // Crear datos de prueba
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);
        $this->project = Project::factory()->create([
            'quote_id' => $quote->id,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(10)
        ]);
        $this->employee = Employee::factory()->create();
    }

    /**
     * Test get timesheets index
     */
    public function test_get_timesheets_index()
    {
        // Crear timesheets de prueba
        Timesheet::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/timesheets');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson(['success' => true]);

        $timesheets = $response->json('data');
        $this->assertCount(3, $timesheets);
    }

    /**
     * Test get timesheets filtered by project
     */
    public function test_get_timesheets_filtered_by_project()
    {
        // Crear otro proyecto
        $client2 = Client::factory()->create();
        $quote2 = Quote::factory()->create(['client_id' => $client2->id]);
        $project2 = Project::factory()->create(['quote_id' => $quote2->id]);

        // Crear timesheets para diferentes proyectos
        Timesheet::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id
        ]);

        Timesheet::factory()->create([
            'project_id' => $project2->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/timesheets?project_id={$this->project->id}");

        $response->assertStatus(200);
        $timesheets = $response->json('data');
        $this->assertCount(2, $timesheets);
    }

    /**
     * Test create timesheet successfully
     */
    public function test_create_timesheet_successfully()
    {
        $timesheetData = [
            'employee_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'shift' => 'morning',
            'check_in_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'break_date' => Carbon::now()->addHours(4)->format('Y-m-d H:i:s'),
            'end_break_date' => Carbon::now()->addHours(5)->format('Y-m-d H:i:s'),
            'check_out_date' => Carbon::now()->addHours(8)->format('Y-m-d H:i:s')
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/timesheets', $timesheetData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'employee_id',
                        'project_id',
                        'shift',
                        'employee',
                        'project'
                    ],
                    'message'
                ])
                ->assertJson(['success' => true]);

        $this->assertDatabaseHas('timesheets', [
            'employee_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'shift' => 'morning'
        ]);
    }

    /**
     * Test create timesheet with invalid data
     */
    public function test_create_timesheet_with_invalid_data()
    {
        $invalidData = [
            'employee_id' => 99999, // ID inexistente
            'project_id' => $this->project->id,
            'shift' => 'invalid_shift',
            'check_in_date' => 'invalid_date'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/timesheets', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['employee_id', 'shift', 'check_in_date']);
    }

    /**
     * Test create timesheet outside project date range
     */
    public function test_create_timesheet_outside_project_date_range()
    {
        $timesheetData = [
            'employee_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'shift' => 'morning',
            'check_in_date' => Carbon::now()->addDays(20)->format('Y-m-d H:i:s') // Fuera del rango
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/timesheets', $timesheetData);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'La fecha del timesheet debe estar dentro del rango del proyecto'
                ]);
    }

    /**
     * Test duplicate timesheet validation
     */
    public function test_duplicate_timesheet_validation()
    {
        $date = Carbon::now()->format('Y-m-d');

        // Crear primer timesheet
        Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id,
            'check_in_date' => $date . ' 08:00:00'
        ]);

        // Intentar crear segundo timesheet para el mismo proyecto y fecha
        $timesheetData = [
            'employee_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'shift' => 'afternoon',
            'check_in_date' => $date . ' 14:00:00'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/timesheets', $timesheetData);

        $response->assertStatus(500); // El modelo debería lanzar excepción
    }

    /**
     * Test get specific timesheet
     */
    public function test_get_specific_timesheet()
    {
        $timesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/timesheets/{$timesheet->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'employee',
                        'project',
                        'attendances'
                    ],
                    'message'
                ]);
    }

    /**
     * Test update timesheet
     */
    public function test_update_timesheet()
    {
        $timesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id,
            'shift' => 'morning'
        ]);

        $updateData = [
            'shift' => 'afternoon',
            'check_out_date' => Carbon::now()->addHours(9)->format('Y-m-d H:i:s')
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson("/api/timesheets/{$timesheet->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $this->assertDatabaseHas('timesheets', [
            'id' => $timesheet->id,
            'shift' => 'afternoon'
        ]);
    }

    /**
     * Test delete timesheet without attendances
     */
    public function test_delete_timesheet_without_attendances()
    {
        $timesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/timesheets/{$timesheet->id}");

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('timesheets', ['id' => $timesheet->id]);
    }

    /**
     * Test get timesheet by project and date
     */
    public function test_get_timesheet_by_project_and_date()
    {
        $date = '2025-07-07';
        $timesheet = Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id,
            'check_in_date' => $date . ' 08:00:00'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/timesheets/project-date?project_id={$this->project->id}&date={$date}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);

        $responseTimesheet = $response->json('data');
        $this->assertEquals($timesheet->id, $responseTimesheet['id']);
    }

    /**
     * Test get timesheet by project and date - not found
     */
    public function test_get_timesheet_by_project_and_date_not_found()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/timesheets/project-date?project_id={$this->project->id}&date=2025-12-25");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No se encontró timesheet para este proyecto en la fecha especificada'
                ]);
    }

    /**
     * Test advanced timesheet search
     */
    public function test_advanced_timesheet_search()
    {
        Timesheet::factory()->create([
            'project_id' => $this->project->id,
            'employee_id' => $this->employee->id,
            'shift' => 'morning',
            'check_in_date' => '2025-07-07 08:00:00'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/timesheets/search?' . http_build_query([
            'project_id' => $this->project->id,
            'shift' => 'morning',
            'specific_date' => '2025-07-07'
        ]));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'statistics',
                    'message',
                    'filters_applied'
                ]);

        $timesheets = $response->json('data');
        $this->assertCount(1, $timesheets);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access()
    {
        $response = $this->getJson('/api/timesheets');
        $response->assertStatus(401);
    }
}
