<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Brand;
use App\Models\Certification;

class BrandAndCertificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Brands
        $brands = [
            ['name' => 'Genérico'],
            ['name' => '3M'],
            ['name' => 'MSA'],
            ['name' => 'Steelpro'],
            ['name' => 'Ansell'],
            ['name' => 'CAT (Caterpillar)'],
            ['name' => 'Fluke'],
            ['name' => 'Kimberly-Clark'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand['name']], $brand);
        }

        // Seed Certifications
        $certifications = [
            [
                'code' => 'Sin Certificación',
                'name' => 'Sin Certificación Especial',
                'description' => 'Equipo o elemento de uso general que no requiere o no posee certificación técnica homologada.',
            ],
            [
                'code' => 'ANSI Z87.1',
                'name' => 'Protección Ocular y Facial contra Impactos',
                'description' => 'Estándar americano para dispositivos de protección de ojos y cara contra impactos y radiaciones.',
            ],
            [
                'code' => 'ANSI Z89.1',
                'name' => 'Protección de Cabeza Industrial',
                'description' => 'Estándar para cascos industriales de protección que mitigan impactos superiores y descargas eléctricas.',
            ],
            [
                'code' => 'EN 388',
                'name' => 'Guantes de Protección contra Riesgos Mecánicos',
                'description' => 'Norma europea para guantes de protección contra abrasión, corte, desgarro y perforación.',
            ],
            [
                'code' => 'NFPA 70E',
                'name' => 'Seguridad Eléctrica en Lugares de Trabajo',
                'description' => 'Estándar para la seguridad eléctrica que previene riesgos de choque y arco eléctrico (Arc Flash).',
            ],
            [
                'code' => 'ASTM F2413',
                'name' => 'Requisitos de Desempeño para Calzado de Seguridad',
                'description' => 'Especificación estándar para calzado de protección con punta de seguridad y resistencia a impactos/compresión.',
            ],
            [
                'code' => 'ANSI Z359.1',
                'name' => 'Sistemas de Protección Contra Caídas',
                'description' => 'Requisitos de seguridad para componentes y sistemas personales de detención de caídas.',
            ],
            [
                'code' => 'CAT III (IEC 61010)',
                'name' => 'Seguridad en Instrumentos de Medida Eléctrica',
                'description' => 'Certificación para instrumentos de prueba y medición eléctrica utilizados en la distribución trifásica (instrumentación).',
            ],
        ];

        foreach ($certifications as $cert) {
            Certification::firstOrCreate(['code' => $cert['code']], $cert);
        }
    }
}
