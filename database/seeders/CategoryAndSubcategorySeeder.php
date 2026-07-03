<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Category;

class CategoryAndSubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Protección de Cabeza',
                'description' => 'EPP para proteger el cráneo de impactos, caída de objetos, salpicaduras y riesgos eléctricos.',
                'subcategories' => [
                    'Cascos de seguridad',
                    'Gorros y capuchas',
                ],
            ],
            [
                'name' => 'Protección Ocular y Facial',
                'description' => 'Equipos para la protección de ojos y cara frente a partículas voladoras, radiación o salpicaduras químicas.',
                'subcategories' => [
                    'Lentes de seguridad transparentes/oscuros',
                    'Caretas de soldar (Trabajo en Caliente)',
                    'Caretas de esmerilar',
                ],
            ],
            [
                'name' => 'Protección Auditiva',
                'description' => 'EPP diseñado para reducir los niveles de ruido nocivos para la audición.',
                'subcategories' => [
                    'Tapones de oído de silicona/espuma',
                    'Orejeras tipo copa (adaptable a casco/diadema)',
                ],
            ],
            [
                'name' => 'Protección Respiratoria',
                'description' => 'Protección contra polvos, humos, gases, vapores o deficiencia de oxígeno.',
                'subcategories' => [
                    'Mascarillas descartables N95/FFP2',
                    'Respiradores de media cara con filtros',
                    'Respiradores de cara completa (Full Face)',
                ],
            ],
            [
                'name' => 'Protección de Manos',
                'description' => 'Guantes protectores contra riesgos mecánicos, químicos, térmicos o eléctricos.',
                'subcategories' => [
                    'Guantes de cuero/badana',
                    'Guantes de nitrilo/neopreno (químicos)',
                    'Guantes de caña larga de cuero (soldadura/caliente)',
                    'Guantes dieléctricos (Trabajo Eléctrico)',
                ],
            ],
            [
                'name' => 'Protección de Pies',
                'description' => 'Calzado especial para evitar lesiones por impacto, compresión, descargas o resbalones.',
                'subcategories' => [
                    'Calzado de seguridad con punta de acero',
                    'Calzado dieléctrico (sin metal)',
                    'Botas de jebe/neopreno',
                ],
            ],
            [
                'name' => 'Protección Corporal y Ropa de Trabajo',
                'description' => 'Vestimenta protectora para el cuerpo entero o partes específicas frente a riesgos industriales.',
                'subcategories' => [
                    'Overol/Mameluco de algodón o ignífugo',
                    'Casaca/Mandil de cuero de cromo (Trabajo en Caliente)',
                    'Chalecos de seguridad de alta visibilidad',
                ],
            ],
            [
                'name' => 'Protección Contra Caídas (Trabajo en Altura)',
                'description' => 'Equipos para detención de caídas libres y trabajos seguros en alturas superiores a 1.80 metros.',
                'subcategories' => [
                    'Arnés de cuerpo entero',
                    'Líneas de vida con amortiguador de impacto',
                    'Conectores de anclaje (eslingas)',
                ],
            ],
            [
                'name' => 'EPP para Trabajos Eléctricos',
                'description' => 'Equipamiento de alta especialización para riesgos de arco eléctrico, cortocircuitos o alta tensión.',
                'subcategories' => [
                    'Traje para arco eléctrico (Arc Flash)',
                    'Caretas de protección facial dieléctrica',
                    'Banquetas y pértigas dieléctricas',
                ],
            ],
        ];

        foreach ($data as $item) {
            $category = Category::create([
                'name' => $item['name'],
                'description' => $item['description'],
            ]);

            foreach ($item['subcategories'] as $subcategoryName) {
                $category->subcategories()->create([
                    'name' => $subcategoryName,
                ]);
            }
        }
    }
}
