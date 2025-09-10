<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProyectosSeeder extends Seeder
{
    public function run()
    {
        $raw = [
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-06'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-12-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-09-02'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-30'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-30'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-13'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-20'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-12-31'],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-08-31'],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-07-31'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-06'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-08-01'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-08-09'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-31'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-04'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-08-15'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-08-03'],
            ['name' => 'PACASMAYO TRUJILLO', 'end_date' => '2025-08-13'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2026-01-29'],
            ['name' => 'PACASMAYO TRUJILLO', 'end_date' => '2026-01-31'],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-08-10'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-08-20'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-08-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-12-31'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-07-31'],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-08-10'],
            ['name' => 'AGROAURORA', 'end_date' => '2025-07-31'],
            ['name' => 'GERENCIA', 'end_date' => null],
            ['name' => 'GERENCIA', 'end_date' => '2025-07-31'],
            ['name' => 'GERENCIA', 'end_date' => null],
            ['name' => 'GERENCIA', 'end_date' => '2025-07-31'],
            ['name' => 'GERENCIA', 'end_date' => null],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-07-31'],
            ['name' => 'OUTSOURCING CPPIU', 'end_date' => '2025-07-31'],
            ['name' => 'GERENCIA', 'end_date' => null],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-07-31'],
            ['name' => 'SUPER FOOD', 'end_date' => '2025-08-23'],
            ['name' => 'AGROAURORA', 'end_date' => '2026-01-27'],
            ['name' => 'TALLER PIURA', 'end_date' => '2025-07-31'],
            ['name' => 'OFICINA - PIURA', 'end_date' => '2025-08-07'],
        ];
        $proyectos = [];
        foreach ($raw as $item) {
            if ($item['end_date']) {
                $start = date('Y-m-d', strtotime('-4 months', strtotime($item['end_date'])));
                $proyectos[] = [
                    'name' => $item['name'],
                    'start_date' => $start,
                    'end_date' => $item['end_date'],
                ];
            } else {
                $proyectos[] = [
                    'name' => $item['name'],
                    'start_date' => null,
                    'end_date' => null,
                ];
            }
        }
        DB::table('projects')->upsert($proyectos, ['name','start_date','end_date'], ['name','start_date','end_date']);
    }
}
