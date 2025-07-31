<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;

class PositionSeeder extends Seeder
{
    public function run()
    {
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
        foreach (array_unique($positions) as $name) {
            Position::firstOrCreate(['name' => $name]);
        }
    }
}
