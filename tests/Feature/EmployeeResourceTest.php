<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_an_employee_without_user()
    {
        // Simulamos el login de un usuario administrador
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        // Datos para crear un empleado sin usuario
        $employeeData = [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'document_number' => '123456',
            'is_active' => true,
            'user_id' => null,  // No asignamos un usuario
        ];

        // Enviamos la solicitud POST para crear el empleado
        $response = $this->post(route('filament.resources.employees.create'), $employeeData);

        // Verificamos que el empleado ha sido creado en la base de datos
        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'document_number' => '123456',
        ]);

        // Verificamos que no se haya creado un usuario
        $this->assertDatabaseMissing('users', [
            'email' => 'johndoe@example.com',
        ]);

        // Verificamos que la respuesta contenga un mensaje de confirmación
        $response->assertSessionHas('message', 'Empleado creado con éxito.');
    }

    /** @test */
    public function it_creates_an_employee_with_user()
    {
        // Simulamos el login de un usuario administrador
        $adminUser = User::factory()->create(['is_admin' => true]);
        $this->actingAs($adminUser);

        // Datos para crear un empleado con un usuario
        $employeeData = [
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'document_number' => '654321',
            'user' => [
                'name' => 'Jane Doe',
                'email' => 'janedoe@example.com',
                'password' => 'securepassword',
                'is_active' => true,
            ]
        ];

        // Enviamos la solicitud POST para crear el empleado con el usuario
        $response = $this->post(route('filament.resources.employees.create'), $employeeData);

        // Verificamos que el empleado se ha creado en la base de datos
        $this->assertDatabaseHas('employees', [
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
        ]);

        // Verificamos que el usuario también ha sido creado
        $this->assertDatabaseHas('users', [
            'email' => 'janedoe@example.com',
            'is_active' => true,
        ]);

        // Verificamos que el id del usuario esté asociado al empleado
        $employee = Employee::where('email', 'janedoe@example.com')->first();
        $this->assertNotNull($employee->user_id);
        $this->assertDatabaseHas('users', ['id' => $employee->user_id]);

        // Aseguramos que la respuesta contenga un mensaje de confirmación
        $response->assertSessionHas('message', 'Empleado y usuario creados con éxito.');
    }
}
