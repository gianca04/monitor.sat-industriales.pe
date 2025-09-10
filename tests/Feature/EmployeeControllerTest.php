<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_employees()
    {
        $employees = Employee::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'document_type',
                        'document_number',
                        'address',
                        'date_birth',
                        'date_contract',
                        'sex',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_employees_by_document_type()
    {
        $dniEmployee = Employee::factory()->create(['document_type' => 'DNI']);
        $passportEmployee = Employee::factory()->create(['document_type' => 'PASAPORTE']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees?document_type=DNI');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('DNI', $data[0]['document_type']);
    }

    /** @test */
    public function it_can_search_employees_by_name()
    {
        $employee1 = Employee::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez'
        ]);
        $employee2 = Employee::factory()->create([
            'first_name' => 'María',
            'last_name' => 'García'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees?search=Juan');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $found = false;
        foreach ($data as $emp) {
            if (str_contains($emp['first_name'], 'Juan') || str_contains($emp['last_name'], 'Juan') || str_contains($emp['document_number'], 'Juan')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /** @test */
    public function it_can_search_employees_by_document_number()
    {
        $employee = Employee::factory()->create(['document_number' => '12345678']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees?search=12345678');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $found = false;
        foreach ($data as $emp) {
            if (str_contains($emp['document_number'], '12345678')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /** @test */
    public function it_can_show_specific_employee()
    {
        $employee = Employee::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'document_type',
                    'document_number',
                    'address',
                    'date_birth',
                    'date_contract',
                    'sex',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_employee()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_employee()
    {
        $employeeData = [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_type' => 'DNI',
            'document_number' => '12345678',
            'address' => 'Av. Test 123',
            'date_birth' => '1990-01-01',
            'date_contract' => '2023-01-01',
            'sex' => 'male',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('employees', [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_number' => '12345678'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/employees', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'document_type',
                'document_number'
            ]);
    }

    /** @test */
    public function it_validates_document_type_values()
    {
        $employeeData = [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_type' => 'INVALID_TYPE',
            'document_number' => '12345678',
            'address' => 'Av. Test 123',
            'date_birth' => '1990-01-01',
            'date_contract' => '2023-01-01',
            'sex' => 'male',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type']);
    }

    /** @test */
    public function it_validates_unique_document_number()
    {
        $existingEmployee = Employee::factory()->create(['document_number' => '12345678']);

        $employeeData = [
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_type' => 'DNI',
            'document_number' => '12345678',
            'address' => 'Av. Test 123',
            'date_birth' => '1990-01-01',
            'date_contract' => '2023-01-01',
            'sex' => 'male',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_number']);
    }

    /** @test */
    public function it_can_update_employee()
    {
        $employee = Employee::factory()->create();

        $updateData = [
            'first_name' => 'Juan Carlos',
            'last_name' => 'García'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Juan Carlos',
            'last_name' => 'García'
        ]);
    }

    /** @test */
    public function it_can_delete_employee()
    {
        $employee = Employee::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    /** @test */
    public function it_can_search_employees_advanced()
    {
        $employee1 = Employee::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_type' => 'DNI'
        ]);

        $employee2 = Employee::factory()->create([
            'first_name' => 'María',
            'last_name' => 'García',
            'document_type' => 'PASAPORTE'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees/search?first_name=Juan&document_type=DNI');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_get_available_employees_for_project()
    {
        $employees = Employee::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/employees/available/project?date=2024-01-15');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            ['GET', '/api/employees'],
            ['GET', '/api/employees/1'],
            ['POST', '/api/employees'],
            ['PUT', '/api/employees/1'],
            ['DELETE', '/api/employees/1'],
            ['GET', '/api/employees/search'],
            ['GET', '/api/employees/available/project'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url, []);
            $response->assertStatus(401);
        }
    }
}
