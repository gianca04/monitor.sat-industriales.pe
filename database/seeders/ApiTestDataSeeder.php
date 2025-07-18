<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;
use App\Models\SubClient;
use App\Models\Quote;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ApiTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear usuarios de prueba
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );

        $supervisorUser = User::firstOrCreate(
            ['email' => 'supervisor@example.com'],
            [
                'name' => 'Supervisor',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );

        // 2. Crear empleados de prueba
        $employees = [
            [
                'document_type' => 'DNI',
                'document_number' => '12345678',
                'first_name' => 'Juan Carlos',
                'last_name' => 'Pérez González',
                'address' => 'Av. Principal 123',
                'date_contract' => '2024-01-15',
                'date_birth' => '1990-05-20',
                'sex' => 'male',
            ],
            [
                'document_type' => 'DNI',
                'document_number' => '87654321',
                'first_name' => 'María Elena',
                'last_name' => 'García López',
                'address' => 'Jr. Secundario 456',
                'date_contract' => '2024-02-01',
                'date_birth' => '1985-12-10',
                'sex' => 'female',
            ],
            [
                'document_type' => 'DNI',
                'document_number' => '11223344',
                'first_name' => 'Carlos Alberto',
                'last_name' => 'Rodríguez Silva',
                'address' => 'Calle Tercera 789',
                'date_contract' => '2024-01-20',
                'date_birth' => '1988-08-15',
                'sex' => 'male',
            ],
            [
                'document_type' => 'DNI',
                'document_number' => '44332211',
                'first_name' => 'Ana Patricia',
                'last_name' => 'Martínez Vega',
                'address' => 'Av. Cuarta 101',
                'date_contract' => '2024-03-01',
                'date_birth' => '1992-03-25',
                'sex' => 'female',
            ],
            [
                'document_type' => 'DNI',
                'document_number' => '55667788',
                'first_name' => 'Roberto Miguel',
                'last_name' => 'Fernández Castro',
                'address' => 'Jr. Quinta 202',
                'date_contract' => '2024-02-15',
                'date_birth' => '1987-11-30',
                'sex' => 'male',
            ],
        ];

        foreach ($employees as $employeeData) {
            Employee::firstOrCreate(
                ['document_number' => $employeeData['document_number']],
                $employeeData
            );
        }

        // 3. Crear clientes de prueba
        $clients = [
            [
                'document_type' => 'RUC',
                'document_number' => '20123456789',
                'person_type' => 'juridica',
                'business_name' => 'Constructora ABC S.A.C.',
                'description' => 'Empresa constructora especializada en obras civiles',
                'address' => 'Av. Industrial 500',
                'contact_phone' => '01-2345678',
                'contact_email' => 'contacto@constructoraabc.com',
            ],
            [
                'document_type' => 'RUC',
                'document_number' => '20987654321',
                'person_type' => 'juridica',
                'business_name' => 'Inmobiliaria XYZ E.I.R.L.',
                'description' => 'Desarrollo inmobiliario y construcción',
                'address' => 'Jr. Comercial 300',
                'contact_phone' => '01-8765432',
                'contact_email' => 'info@inmobiliariaxyz.com',
            ],
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(
                ['document_number' => $clientData['document_number']],
                $clientData
            );
        }

        // 4. Crear sub-clientes
        $client1 = Client::where('document_number', '20123456789')->first();
        $client2 = Client::where('document_number', '20987654321')->first();

        if ($client1) {
            SubClient::firstOrCreate(
                ['client_id' => $client1->id, 'name' => 'Obra Norte'],
                [
                    'description' => 'Proyecto de construcción sector norte',
                    'location' => 'Lima Norte',
                    'latitude' => -11.9891,
                    'longitude' => -77.0089,
                ]
            );

            SubClient::firstOrCreate(
                ['client_id' => $client1->id, 'name' => 'Obra Sur'],
                [
                    'description' => 'Proyecto de construcción sector sur',
                    'location' => 'Lima Sur',
                    'latitude' => -12.1891,
                    'longitude' => -76.9989,
                ]
            );
        }

        if ($client2) {
            SubClient::firstOrCreate(
                ['client_id' => $client2->id, 'name' => 'Edificio Central'],
                [
                    'description' => 'Construcción de edificio residencial',
                    'location' => 'Lima Centro',
                    'latitude' => -12.0464,
                    'longitude' => -77.0428,
                ]
            );
        }

        // 5. Crear cotizaciones
        $employee1 = Employee::where('document_number', '12345678')->first();
        $subClient1 = SubClient::where('name', 'Obra Norte')->first();
        $subClient2 = SubClient::where('name', 'Edificio Central')->first();

        if ($employee1 && $subClient1) {
            $quote1 = Quote::firstOrCreate(
                ['TDR' => 'TDR-001-2025'],
                [
                    'client_id' => $subClient1->client_id,
                    'employee_id' => $employee1->id,
                    'sub_client_id' => $subClient1->id,
                    'contractor' => 'Contratista Norte SAC',
                    'pe_pt' => 'PE',
                    'project_description' => 'Proyecto de construcción de infraestructura vial',
                    'location' => 'Lima Norte - Sector Industrial',
                    'delivery_term' => '2025-12-31',
                    'status' => 'accepted',
                    'comment' => 'Proyecto aprobado para ejecución',
                ]
            );

            // 6. Crear proyectos
            if ($quote1) {
                $project1 = Project::firstOrCreate(
                    ['name' => 'Construcción Vial Norte'],
                    [
                        'start_date' => '2025-01-01',
                        'end_date' => '2025-12-31',
                        'location' => json_encode([
                            'address' => 'Lima Norte - Sector Industrial',
                            'reference' => 'Altura del km 15 Panamericana Norte'
                        ]),
                        'latitude' => -11.9891,
                        'longitude' => -77.0089,
                        'quote_id' => $quote1->id,
                    ]
                );
            }
        }

        if ($employee1 && $subClient2) {
            $quote2 = Quote::firstOrCreate(
                ['TDR' => 'TDR-002-2025'],
                [
                    'client_id' => $subClient2->client_id,
                    'employee_id' => $employee1->id,
                    'sub_client_id' => $subClient2->id,
                    'contractor' => 'Constructora Central EIRL',
                    'pe_pt' => 'PT',
                    'project_description' => 'Edificio residencial de 8 pisos',
                    'location' => 'Lima Centro - Miraflores',
                    'delivery_term' => '2025-08-31',
                    'status' => 'accepted',
                    'comment' => 'Proyecto residencial aprobado',
                ]
            );

            if ($quote2) {
                $project2 = Project::firstOrCreate(
                    ['name' => 'Edificio Residencial Miraflores'],
                    [
                        'start_date' => '2025-02-01',
                        'end_date' => '2025-08-31',
                        'location' => json_encode([
                            'address' => 'Av. Larco 1234, Miraflores',
                            'reference' => 'Frente al parque Kennedy'
                        ]),
                        'latitude' => -12.1215,
                        'longitude' => -77.0269,
                        'quote_id' => $quote2->id,
                    ]
                );
            }
        }

        // 7. Crear timesheets de ejemplo
        $project1 = Project::where('name', 'Construcción Vial Norte')->first();
        $project2 = Project::where('name', 'Edificio Residencial Miraflores')->first();
        $allEmployees = Employee::all();

        if ($project1 && $allEmployees->count() > 0) {
            // Timesheet para proyecto 1 - fecha actual
            $timesheet1 = Timesheet::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'check_in_date' => Carbon::today()->setHour(8)
                ],
                [
                    'employee_id' => $allEmployees->first()->id,
                    'shift' => 'day',
                    'break_date' => Carbon::today()->setHour(12),
                    'end_break_date' => Carbon::today()->setHour(13),
                    'check_out_date' => Carbon::today()->setHour(17),
                ]
            );

            // Timesheet para proyecto 1 - ayer
            $timesheet2 = Timesheet::firstOrCreate(
                [
                    'project_id' => $project1->id,
                    'check_in_date' => Carbon::yesterday()->setHour(8)
                ],
                [
                    'employee_id' => $allEmployees->first()->id,
                    'shift' => 'day',
                    'break_date' => Carbon::yesterday()->setHour(12),
                    'end_break_date' => Carbon::yesterday()->setHour(13),
                    'check_out_date' => Carbon::yesterday()->setHour(17),
                ]
            );
        }

        if ($project2 && $allEmployees->count() > 0) {
            // Timesheet para proyecto 2 - fecha actual
            $timesheet3 = Timesheet::firstOrCreate(
                [
                    'project_id' => $project2->id,
                    'check_in_date' => Carbon::today()->setHour(7)
                ],
                [
                    'employee_id' => $allEmployees->skip(1)->first()->id,
                    'shift' => 'day',
                    'break_date' => Carbon::today()->setHour(12),
                    'end_break_date' => Carbon::today()->setHour(13),
                    'check_out_date' => Carbon::today()->setHour(16),
                ]
            );
        }

        // 8. Crear asistencias de ejemplo
        $timesheets = Timesheet::all();

        foreach ($timesheets as $timesheet) {
            // Crear asistencias para algunos empleados
            $employeesToAssign = $allEmployees->random(min(3, $allEmployees->count()));

            foreach ($employeesToAssign as $index => $employee) {
                // Evitar duplicados
                $existingAttendance = Attendance::where('timesheet_id', $timesheet->id)
                    ->where('employee_id', $employee->id)
                    ->first();

                if (!$existingAttendance) {
                    $statuses = ['present', 'late', 'absent', 'permission'];
                    $shifts = ['day', 'night'];

                    Attendance::create([
                        'timesheet_id' => $timesheet->id,
                        'employee_id' => $employee->id,
                        'status' => $statuses[$index % count($statuses)],
                        'shift' => $shifts[$index % count($shifts)],
                        'check_in_date' => $timesheet->check_in_date,
                        'break_date' => $timesheet->break_date,
                        'end_break_date' => $timesheet->end_break_date,
                        'check_out_date' => $timesheet->check_out_date,
                        'observation' => 'Asistencia de prueba - ' . $statuses[$index % count($statuses)],
                    ]);
                }
            }
        }

        $this->command->info('Datos de prueba para API creados exitosamente:');
        $this->command->info('- Usuarios: admin@example.com y supervisor@example.com (password: password123)');
        $this->command->info('- Empleados: ' . Employee::count());
        $this->command->info('- Clientes: ' . Client::count());
        $this->command->info('- Sub-clientes: ' . SubClient::count());
        $this->command->info('- Cotizaciones: ' . Quote::count());
        $this->command->info('- Proyectos: ' . Project::count());
        $this->command->info('- Timesheets: ' . Timesheet::count());
        $this->command->info('- Asistencias: ' . Attendance::count());
    }
}
