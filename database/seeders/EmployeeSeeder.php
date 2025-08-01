<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        // Definir los cargos y obtener o crear los IDs
        $positions = [
            'TECNICO MECANICO',
            'GERENTE DE OPERACIONES',
            'PRACTICANTE DE SISTEMAS',
            'ASISTENTE DE ALMACEN Y LOGISTICA',
            'ASISTENTE DE INGENIERIA Y PROYECTOS',
            'TECNICO INSTRUMENTISTA',
            'SUPERVISOR SSOMA',
            'INGENIERO DE PROYECTOS',
            'TECNICO ELECTRICISTA',
            'PREVENCIONISTA',
            'ASISTENTE DE CONTROL & INSTRUMENTACION',
            'SUPERVISOR DE PROYECTOS E INGENIERIA',
            'ASISTENTE DE RECURSOS HUMANOS',
            'TECNICO DE AIRE ACONDICIONADO',
            'SUPERVISOR ELECTRONICO',
            'SUPERVISOR DE CONTROL & INSTRUMENTACION',
            'ASISTENTE ADMINISTRATIVA',
            'INGENIERO RESIDENTE',
            'CONDUCTOR',
            'ASISTENTE DE OPERACIONES',
            'GERENCIA GENERAL',
            'GERENTE ADMINISTRATIVO',
            'GERENTE TECNICO',
            'ADMINISTRATIVO',
            'ASISTENTE DE GERENCIA TECNICA',
        ];
        $positionIds = [];
        foreach ($positions as $pos) {
            $positionIds[$pos] = Position::firstOrCreate(['name' => $pos])->id;
        }

        // Datos de empleados
        $employees = [
            [ 'first_name' => 'JACKSON JHONNY', 'last_name' => 'AGUILERA OLAYA', 'document_number' => '74876887', 'position' => 'TECNICO MECANICO', 'date_contract' => '2025-02-05', 'active' => true ],
            [ 'first_name' => 'JOSE LUIS', 'last_name' => 'ANZA COLMENARES', 'document_number' => '002874944', 'position' => 'GERENTE DE OPERACIONES', 'date_contract' => '2024-02-05', 'active' => true ],
            [ 'first_name' => 'GIAN CARLOS', 'last_name' => 'AVALO GALLARDO', 'document_number' => '60843750', 'position' => 'PRACTICANTE DE SISTEMAS', 'date_contract' => '2025-03-27', 'active' => true ],
            [ 'first_name' => 'ALEX CARLOS', 'last_name' => 'BALLESTEROS GOMEZ', 'document_number' => '75171427', 'position' => 'ASISTENTE DE ALMACEN Y LOGISTICA', 'date_contract' => '2025-03-03', 'active' => true ],
            [ 'first_name' => 'MARIO LUIS', 'last_name' => 'CALVA ALCAS', 'document_number' => '70886956', 'position' => 'ASISTENTE DE INGENIERIA Y PROYECTOS', 'date_contract' => '2024-01-08', 'active' => true ],
            [ 'first_name' => 'ALEXIS AARON', 'last_name' => 'CARREÑO PULACHE', 'document_number' => '71536378', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2025-07-16', 'active' => true ],
            [ 'first_name' => 'JESUS ALFONSO ANTHONY', 'last_name' => 'CASTAÑEDA GUTIERREZ', 'document_number' => '71597118', 'position' => 'SUPERVISOR SSOMA', 'date_contract' => '2024-11-04', 'active' => true ],
            [ 'first_name' => 'JORGE ARMANDO', 'last_name' => 'CHAVEZ JAVIER', 'document_number' => '42552917', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2024-11-04', 'active' => true ],
            [ 'first_name' => 'OSCAR JUNIOR', 'last_name' => 'CHIROQUE CORDOVA', 'document_number' => '73239574', 'position' => 'INGENIERO DE PROYECTOS', 'date_contract' => '2025-07-16', 'active' => true ],
            [ 'first_name' => 'ALEXIS ALDAIR', 'last_name' => 'CHUYES RAMOS', 'document_number' => '76092393', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2025-07-14', 'active' => true ],
            [ 'first_name' => 'WILSON MERURY', 'last_name' => 'CORNEJO AGUIRRE', 'document_number' => '44552219', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2024-11-20', 'active' => true ],
            [ 'first_name' => 'LUIS MARIO DAVID', 'last_name' => 'DELGADO ROSAS', 'document_number' => '74886963', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2025-05-21', 'active' => true ],
            [ 'first_name' => 'WHITNEY MAOLY', 'last_name' => 'DIOSES MORALES', 'document_number' => '74570131', 'position' => 'PREVENCIONISTA', 'date_contract' => '2025-02-05', 'active' => true ],
            [ 'first_name' => 'LUIS ESTEBAN', 'last_name' => 'ESTRADA TROYA', 'document_number' => '76509847', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2023-11-15', 'active' => true ],
            [ 'first_name' => 'MILTON EDUARDO', 'last_name' => 'FLORES LUDEÑA', 'document_number' => '71078311', 'position' => 'ASISTENTE DE CONTROL & INSTRUMENTACION', 'date_contract' => '2024-07-22', 'active' => true ],
            [ 'first_name' => 'DEYVI ARNOLD', 'last_name' => 'GARCIA MIO', 'document_number' => '76944077', 'position' => 'SUPERVISOR DE PROYECTOS E INGENIERIA', 'date_contract' => '2024-06-24', 'active' => true ],
            [ 'first_name' => 'JOEL AARON', 'last_name' => 'HUERTAS DIOSES', 'document_number' => '75021105', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2025-07-07', 'active' => true ],
            [ 'first_name' => 'JOSE MERCEDES', 'last_name' => 'JUAREZ SILVA', 'document_number' => '43366522', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2024-11-08', 'active' => true ],
            [ 'first_name' => 'DANIEL EDUARDO', 'last_name' => 'MADRID MADRID', 'document_number' => '72322493', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2025-07-02', 'active' => true ],
            [ 'first_name' => 'PEDRO MIGUEL', 'last_name' => 'MARTINEZ VILCHEZ', 'document_number' => '72391165', 'position' => 'TECNICO MECANICO', 'date_contract' => '2021-08-24', 'active' => true ],
            [ 'first_name' => 'JOSE ANTONIO', 'last_name' => 'MECHATO COVEÑAS', 'document_number' => '72206128', 'position' => 'ASISTENTE DE INGENIERIA Y PROYECTOS', 'date_contract' => '2025-01-08', 'active' => true ],
            [ 'first_name' => 'CRISTIAM AUGUSTO', 'last_name' => 'MENDOZA VILCHEZ', 'document_number' => '44719610', 'position' => 'TECNICO MECANICO', 'date_contract' => '2023-02-06', 'active' => true ],
            [ 'first_name' => 'JUAN CARLOS', 'last_name' => 'MENDOZA VILCHEZ', 'document_number' => '72738278', 'position' => 'TECNICO MECANICO', 'date_contract' => '2025-03-05', 'active' => true ],
            [ 'first_name' => 'EDWIN EDUARDO', 'last_name' => 'PALACIOS GOMEZ', 'document_number' => '47601731', 'position' => 'ASISTENTE DE ALMACEN Y LOGISTICA', 'date_contract' => '2024-06-13', 'active' => true ],
            [ 'first_name' => 'TATIANA DEL ROCIO', 'last_name' => 'PAUCAR GOMEZ', 'document_number' => '76201905', 'position' => 'ASISTENTE DE RECURSOS HUMANOS', 'date_contract' => '2022-02-07', 'active' => true ],
            [ 'first_name' => 'VICTOR BACNER', 'last_name' => 'PEÑA GARCIA', 'document_number' => '76550191', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2023-08-05', 'active' => true ],
            [ 'first_name' => 'ERICK LUIS FERNANDO', 'last_name' => 'PULACHE VILCHEZ', 'document_number' => '75454386', 'position' => 'ASISTENTE DE RECURSOS HUMANOS', 'date_contract' => '2025-05-20', 'active' => true ],
            [ 'first_name' => 'MARLON EROS', 'last_name' => 'QUEVEDO TERAN CESAR', 'document_number' => '76677571', 'position' => 'ASISTENTE DE INGENIERIA Y PROYECTOS', 'date_contract' => '2025-07-14', 'active' => true ],
            [ 'first_name' => 'JULIO ALEXANDER', 'last_name' => 'QUINTANA OLAZABAL', 'document_number' => '72788358', 'position' => 'TECNICO DE AIRE ACONDICIONADO', 'date_contract' => '2023-09-01', 'active' => true ],
            [ 'first_name' => 'ANDERSON ALEXIS', 'last_name' => 'QUINTANA SULLON', 'document_number' => '48554595', 'position' => 'TECNICO DE AIRE ACONDICIONADO', 'date_contract' => '2024-12-03', 'active' => true ],
            [ 'first_name' => 'ELBERTH DANIEL', 'last_name' => 'RIVERA CASTILLO', 'document_number' => '44901194', 'position' => 'SUPERVISOR ELECTRONICO', 'date_contract' => '2024-11-19', 'active' => true ],
            [ 'first_name' => 'MANUEL ISIDRO', 'last_name' => 'RODRIGUEZ CHAMBERGO', 'document_number' => '71538260', 'position' => 'SUPERVISOR DE CONTROL & INSTRUMENTACION', 'date_contract' => '2021-12-01', 'active' => true ],
            [ 'first_name' => 'CARLOS HUMBERTO', 'last_name' => 'ROJAS TIMOTEO', 'document_number' => '46974407', 'position' => 'SUPERVISOR DE CONTROL & INSTRUMENTACION', 'date_contract' => '2022-12-26', 'active' => true ],
            [ 'first_name' => 'ALEJANDRA MELISSA ISABEL', 'last_name' => 'RUBIO ANCAJIMA', 'document_number' => '75706767', 'position' => 'ASISTENTE ADMINISTRATIVA', 'date_contract' => '2025-03-11', 'active' => true ],
            [ 'first_name' => 'MIGUEL FRANCISCO', 'last_name' => 'SAAVEDRA MOGOLLON', 'document_number' => '41684914', 'position' => 'INGENIERO RESIDENTE', 'date_contract' => '2024-11-09', 'active' => true ],
            [ 'first_name' => 'ROBERTO', 'last_name' => 'SANCHEZ MENDOZA', 'document_number' => '02858039', 'position' => 'CONDUCTOR', 'date_contract' => '2025-04-21', 'active' => true ],
            [ 'first_name' => 'FELIX', 'last_name' => 'SANTISTEBAN BERECHE', 'document_number' => '17445170', 'position' => 'TECNICO MECANICO', 'date_contract' => '2023-02-06', 'active' => true ],
            [ 'first_name' => 'ALEXANDRA CAROLINA', 'last_name' => 'SILVA CORDOVA', 'document_number' => '74413817', 'position' => 'ASISTENTE DE OPERACIONES', 'date_contract' => '2025-03-25', 'active' => true ],
            [ 'first_name' => 'DIEGO FERNANDO', 'last_name' => 'SOTO CASTILLO', 'document_number' => '48578169', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2025-03-17', 'active' => true ],
            [ 'first_name' => 'JAIRO LENNYN', 'last_name' => 'SUAREZ MURILLO', 'document_number' => '72623787', 'position' => 'ASISTENTE DE INGENIERIA Y PROYECTOS', 'date_contract' => '2025-07-11', 'active' => true ],
            [ 'first_name' => 'LUIS MOISES', 'last_name' => 'TORRES ALVAREZ', 'document_number' => '74285289', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2025-05-19', 'active' => true ],
            [ 'first_name' => 'KARINA MEDALIT', 'last_name' => 'TROYA VELASQUEZ DE MORALES', 'document_number' => '41060969', 'position' => 'GERENCIA GENERAL', 'date_contract' => null, 'active' => true ],
            [ 'first_name' => 'DIANA MABEL', 'last_name' => 'TROYA VELASQUEZ', 'document_number' => '45246095', 'position' => 'ASISTENTE DE ALMACEN Y LOGISTICA', 'date_contract' => '2021-08-02', 'active' => true ],
            [ 'first_name' => 'GUILIANA LISSETY', 'last_name' => 'TROYA VELASQUEZ', 'document_number' => '42671457', 'position' => 'GERENTE ADMINISTRATIVO', 'date_contract' => null, 'active' => true ],
            [ 'first_name' => 'ROSA MERCEDES', 'last_name' => 'TROYA VELÁSQUEZ', 'document_number' => '10286686', 'position' => 'ASISTENTE ADMINISTRATIVA', 'date_contract' => '2021-01-04', 'active' => true ],
            [ 'first_name' => 'SEGUNDO ALBERTO', 'last_name' => 'TROYA VELASQUEZ', 'document_number' => '40060280', 'position' => 'GERENTE TECNICO', 'date_contract' => null, 'active' => true ],
            [ 'first_name' => 'ARACELY YOBANY', 'last_name' => 'VALENCIA SULLON', 'document_number' => '48447868', 'position' => 'ASISTENTE DE RECURSOS HUMANOS', 'date_contract' => '2021-12-01', 'active' => true ],
            [ 'first_name' => 'CARLOS', 'last_name' => 'VEGAS NEYRA', 'document_number' => '47420713', 'position' => 'TECNICO ELECTRICISTA', 'date_contract' => '2024-11-04', 'active' => true ],
            [ 'first_name' => 'SANTOS', 'last_name' => 'VELASQUEZ ADRIANZEN', 'document_number' => '16772277', 'position' => 'ADMINISTRATIVO', 'date_contract' => null, 'active' => true ],
            [ 'first_name' => 'JOEL PAUL', 'last_name' => 'VIERA HERRERA', 'document_number' => '73194491', 'position' => 'ASISTENTE DE GERENCIA TECNICA', 'date_contract' => '2024-08-01', 'active' => true ],
            [ 'first_name' => 'SERGIO FELIX', 'last_name' => 'VILLARREAL ALBURQUEQUE', 'document_number' => '47348470', 'position' => 'SUPERVISOR SSOMA', 'date_contract' => '2025-04-24', 'active' => true ],
            [ 'first_name' => 'JORGE ANTHONY', 'last_name' => 'ZAMORA MEJIA', 'document_number' => '48439842', 'position' => 'INGENIERO DE PROYECTOS', 'date_contract' => '2025-01-28', 'active' => true ],
            [ 'first_name' => 'BRAYER LINO', 'last_name' => 'ZAPATA CASTILLO', 'document_number' => '70043804', 'position' => 'TECNICO INSTRUMENTISTA', 'date_contract' => '2025-01-28', 'active' => true ],
            [ 'first_name' => 'MARIA DEL PILAR', 'last_name' => 'ZAPATA MORE', 'document_number' => '75900365', 'position' => 'PREVENCIONISTA', 'date_contract' => '2025-05-08', 'active' => true ],
        ];

        foreach ($employees as $emp) {
            $docType = (strlen($emp['document_number']) === 8) ? 'DNI' : 'CARNET DE EXTRANJERIA';
            // Campos inventados
            $date_birth = date('Y-m-d', strtotime('-' . rand(25, 55) . ' years -' . rand(0, 364) . ' days'));
            // Detección básica de sexo por nombre
            $femaleNames = ['ALEXANDRA','CAROLINA','DIANA','GUILIANA','ROSA','MERCEDES','MELISSA','ISABEL','ARACELY','YOBANY','MARIA','TATIANA','ALEJANDRA','LISSETY','KARINA','MABEL','JOSE MERCEDES','ALEXANDRA CAROLINA','TATIANA DEL ROCIO','GUILIANA LISSETY','ROSA MERCEDES','MARIA DEL PILAR'];
            $firstName = strtoupper(explode(' ', $emp['first_name'])[0]);
            if (in_array($firstName, $femaleNames)) {
                $sex = 'female';
            } elseif ($firstName === 'OTRO' || $firstName === 'OTHER') {
                $sex = 'other';
            } else {
                $sex = 'male';
            }
            Employee::updateOrCreate(
                [ 'document_number' => $emp['document_number'] ],
                [
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'document_type' => $docType,
                    'document_number' => $emp['document_number'],
                    'date_birth' => $date_birth,
                    'sex' => $sex,
                    'date_contract' => $emp['date_contract'],
                    'active' => $emp['active'],
                    'position_id' => $positionIds[$emp['position']] ?? null,
                ]
            );
        }
    }
}
