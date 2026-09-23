<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Item;
use App\Models\Requirement;
use App\Models\RequirementList;
use App\Models\Subcategory;
use App\Models\SubClient;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RequirementDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Iniciando carga de datos ficticios para el ecosistema de Requerimientos...');

        // 1. Usuario Solicitante
        $user = User::firstOrCreate(
            ['email' => 'admin@sat-industriales.pe'],
            [
                'name' => 'Giancarlo Administrador',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Cliente y Sedes/Tiendas (SubClientes)
        $client = Client::firstOrCreate(
            ['document_number' => '20601234567'],
            [
                'document_type' => 'RUC',
                'person_type' => 'juridica',
                'business_name' => 'SAT Industriales S.A.C.',
                'description' => 'Servicios de ingeniería eléctrica y automatización industrial',
                'address' => 'Av. Argentina 2040, Callao',
                'contact_phone' => '987654321',
                'contact_email' => 'operaciones@sat-industriales.pe',
            ]
        );

        $subClientCallao = SubClient::firstOrCreate(
            ['name' => 'Planta Principal Callao', 'client_id' => $client->id],
            [
                'description' => 'Sede central de operaciones y talleres metalmecánicos',
                'address' => 'Av. Elmer Faucett 1200, Callao',
                'latitude' => -12.04318,
                'longitude' => -77.10821,
            ]
        );

        $subClientLurin = SubClient::firstOrCreate(
            ['name' => 'Sede Logística Lurín', 'client_id' => $client->id],
            [
                'description' => 'Centro de distribución y almacenamiento masivo',
                'address' => 'Km 35 Antigua Panamericana Sur, Lurín',
                'latitude' => -12.27411,
                'longitude' => -76.87123,
            ]
        );

        $subClientSanIsidro = SubClient::firstOrCreate(
            ['name' => 'Oficinas Administrativas San Isidro', 'client_id' => $client->id],
            [
                'description' => 'Sede corporativa y atención comercial',
                'address' => 'Av. Conquistadores 450, San Isidro, Lima',
                'latitude' => -12.09841,
                'longitude' => -77.03612,
            ]
        );

        // 3. Unidades de Medida
        $unitsData = [
            ['name' => 'Metros', 'symbol' => 'm'],
            ['name' => 'Unidades', 'symbol' => 'und'],
            ['name' => 'Kilogramos', 'symbol' => 'kg'],
            ['name' => 'Litros', 'symbol' => 'L'],
            ['name' => 'Galones', 'symbol' => 'gal'],
            ['name' => 'Cajas', 'symbol' => 'cja'],
            ['name' => 'Pares', 'symbol' => 'par'],
            ['name' => 'Bolsas', 'symbol' => 'bls'],
            ['name' => 'Rollos', 'symbol' => 'rll'],
        ];

        $units = [];
        foreach ($unitsData as $u) {
            $units[$u['symbol']] = Unit::firstOrCreate(['name' => $u['name']], $u);
        }

        // 4. Categorías y Subcategorías
        $catalogStructure = [
            'Materiales Eléctricos' => [
                'description' => 'Cables, protecciones, canalizaciones y artefactos de iluminación.',
                'subcategories' => [
                    'Conductores y Cables',
                    'Interruptores y Tableros',
                    'Tuberías y Canaletas',
                    'Luminarias y Lámparas',
                ],
            ],
            'Ferretería y Estructuras' => [
                'description' => 'Elementos de fijación, perfiles, herramientas y consumibles mecánicos.',
                'subcategories' => [
                    'Pernos y Tornillos',
                    'Herramientas Manuales',
                    'Adhesivos y Cintas',
                    'Perfiles y Ángulos',
                ],
            ],
            'Equipos de Protección (EPP)' => [
                'description' => 'Seguridad industrial, salud ocupacional y protección personal.',
                'subcategories' => [
                    'Protección de Cabeza',
                    'Protección Visual y Facial',
                    'Guantes de Seguridad',
                    'Calzado Dieléctrico',
                ],
            ],
        ];

        $subcategories = [];
        $categories = [];

        foreach ($catalogStructure as $catName => $catDetails) {
            $cat = Category::firstOrCreate(
                ['name' => $catName],
                ['description' => $catDetails['description']]
            );
            $categories[$catName] = $cat;

            foreach ($catDetails['subcategories'] as $subName) {
                $sub = Subcategory::firstOrCreate(
                    ['name' => $subName, 'category_id' => $cat->id]
                );
                $subcategories[$subName] = $sub;
            }
        }

        // 5. Ítems de Catálogo con SKUs limpios
        $itemsCatalog = [
            // Materiales Eléctricos
            [
                'name' => 'Cable Vulcanizado 3x14 AWG Indeco',
                'sku' => 'ELE-CAB-001',
                'subcategory' => 'Conductores y Cables',
                'unit' => 'm',
            ],
            [
                'name' => 'Cable THW 4 mm² Rojo Indeco',
                'sku' => 'ELE-CAB-002',
                'subcategory' => 'Conductores y Cables',
                'unit' => 'm',
            ],
            [
                'name' => 'Cable THW 4 mm² Negro Indeco',
                'sku' => 'ELE-CAB-003',
                'subcategory' => 'Conductores y Cables',
                'unit' => 'm',
            ],
            [
                'name' => 'Cable THW 4 mm² Verde/Amarillo Tierra',
                'sku' => 'ELE-CAB-004',
                'subcategory' => 'Conductores y Cables',
                'unit' => 'm',
            ],
            [
                'name' => 'Interruptor Termomagnético 2x32A Schneider',
                'sku' => 'ELE-INT-005',
                'subcategory' => 'Interruptores y Tableros',
                'unit' => 'und',
            ],
            [
                'name' => 'Interruptor Diferencial 2x25A 30mA Schneider',
                'sku' => 'ELE-INT-006',
                'subcategory' => 'Interruptores y Tableros',
                'unit' => 'und',
            ],
            [
                'name' => 'Tablero de Distribución Metálico 12 Polos IP65',
                'sku' => 'ELE-INT-007',
                'subcategory' => 'Interruptores y Tableros',
                'unit' => 'und',
            ],
            [
                'name' => 'Canaleta Plástica 40x25 con División DEXSON',
                'sku' => 'ELE-TUB-008',
                'subcategory' => 'Tuberías y Canaletas',
                'unit' => 'und',
            ],
            [
                'name' => 'Tubo Conduit EMT 3/4" x 3m Galvanizado',
                'sku' => 'ELE-TUB-009',
                'subcategory' => 'Tuberías y Canaletas',
                'unit' => 'und',
            ],
            [
                'name' => 'Curva EMT 3/4" Galvanizada',
                'sku' => 'ELE-TUB-010',
                'subcategory' => 'Tuberías y Canaletas',
                'unit' => 'und',
            ],
            [
                'name' => 'Luminaria Hermética LED Industrial 2x18W IP65',
                'sku' => 'ELE-LUM-011',
                'subcategory' => 'Luminarias y Lámparas',
                'unit' => 'und',
            ],
            [
                'name' => 'Reflector LED 100W IP66 Philips Luz Fría',
                'sku' => 'ELE-LUM-012',
                'subcategory' => 'Luminarias y Lámparas',
                'unit' => 'und',
            ],
            [
                'name' => 'Campana LED High Bay 150W 5000K',
                'sku' => 'ELE-LUM-013',
                'subcategory' => 'Luminarias y Lámparas',
                'unit' => 'und',
            ],

            // Ferretería y Estructuras
            [
                'name' => 'Perno de Anclaje Expansivo 3/8" x 3"',
                'sku' => 'FER-PER-014',
                'subcategory' => 'Pernos y Tornillos',
                'unit' => 'und',
            ],
            [
                'name' => 'Tornillo Autoperforante 1" Cabeza Lenteja Wafer',
                'sku' => 'FER-PER-015',
                'subcategory' => 'Pernos y Tornillos',
                'unit' => 'cja',
            ],
            [
                'name' => 'Tarugo de Plástico con Tope #8',
                'sku' => 'FER-PER-016',
                'subcategory' => 'Pernos y Tornillos',
                'unit' => 'cja',
            ],
            [
                'name' => 'Cinta Aislante 3M Scotch Super 33+ Negra',
                'sku' => 'FER-ADH-017',
                'subcategory' => 'Adhesivos y Cintas',
                'unit' => 'rll',
            ],
            [
                'name' => 'Cinta Autofundente Vulcanizante 3M Scotch 23',
                'sku' => 'FER-ADH-018',
                'subcategory' => 'Adhesivos y Cintas',
                'unit' => 'rll',
            ],
            [
                'name' => 'Silicona Neutra Multiuso Transparente 300ml',
                'sku' => 'FER-ADH-019',
                'subcategory' => 'Adhesivos y Cintas',
                'unit' => 'und',
            ],
            [
                'name' => 'Alicate Universal Aislado 8" 1000V Stanley',
                'sku' => 'FER-HER-020',
                'subcategory' => 'Herramientas Manuales',
                'unit' => 'und',
            ],
            [
                'name' => 'Juego de Destornilladores Aislados 1000V (6 Piezas)',
                'sku' => 'FER-HER-021',
                'subcategory' => 'Herramientas Manuales',
                'unit' => 'cja',
            ],
            [
                'name' => 'Riel Unistrut 41x41x2.0mm x 3m Galvanizado',
                'sku' => 'FER-PER-022',
                'subcategory' => 'Perfiles y Ángulos',
                'unit' => 'und',
            ],
            [
                'name' => 'Abrazadera Riel Unistrut para Tubo EMT 3/4"',
                'sku' => 'FER-PER-023',
                'subcategory' => 'Perfiles y Ángulos',
                'unit' => 'und',
            ],

            // Equipos de Protección (EPP)
            [
                'name' => 'Casco de Seguridad Dieléctrico Blanco Tipo 1 Clase E',
                'sku' => 'EPP-CAB-024',
                'subcategory' => 'Protección de Cabeza',
                'unit' => 'und',
            ],
            [
                'name' => 'Barbiquejo Elástico de 3 Puntos con Mentonera',
                'sku' => 'EPP-CAB-025',
                'subcategory' => 'Protección de Cabeza',
                'unit' => 'und',
            ],
            [
                'name' => 'Lentes de Seguridad Transparentes Antiempañantes 3M',
                'sku' => 'EPP-VIS-026',
                'subcategory' => 'Protección Visual y Facial',
                'unit' => 'und',
            ],
            [
                'name' => 'Careta Facial Transparente de Policarbonato con Cabezal',
                'sku' => 'EPP-VIS-027',
                'subcategory' => 'Protección Visual y Facial',
                'unit' => 'und',
            ],
            [
                'name' => 'Guantes de Cuero Badana para Electricista Talla L',
                'sku' => 'EPP-GUA-028',
                'subcategory' => 'Guantes de Seguridad',
                'unit' => 'par',
            ],
            [
                'name' => 'Guantes de Nitrilo Anticorte Nivel 5 Talla L',
                'sku' => 'EPP-GUA-029',
                'subcategory' => 'Guantes de Seguridad',
                'unit' => 'par',
            ],
            [
                'name' => 'Botines de Seguridad Dieléctricos con Puntera Composite T-42',
                'sku' => 'EPP-CAL-030',
                'subcategory' => 'Calzado Dieléctrico',
                'unit' => 'par',
            ],
        ];

        $createdItems = [];
        foreach ($itemsCatalog as $itemData) {
            $subcat = $subcategories[$itemData['subcategory']] ?? null;
            $unit = $units[$itemData['unit']] ?? null;

            $item = Item::firstOrCreate(
                ['sku' => $itemData['sku']],
                [
                    'name' => $itemData['name'],
                    'subcategory_id' => $subcat?->id,
                    'unit_id' => $unit?->id,
                    'created_by' => $user->id,
                    'photo' => null,
                ]
            );

            $createdItems[] = $item;
        }

        $this->command?->info('Ítems de catálogo registrados: '.count($createdItems));

        // 6. Creación de Requerimientos de Prueba

        // REQUERIMIENTO #1: Con 25 materiales (Ideal para probar paginación de 10 en 10)
        $req1 = Requirement::firstOrCreate(
            [
                'sub_client_id' => $subClientCallao->id,
                'activity_name' => 'Mantenimiento Preventivo y Remodelación Subestación N° 2',
            ],
            [
                'created_by' => $user->id,
            ]
        );

        // Asociar 25 materiales con cantidades representativas
        $quantities = [
            1 => 120.5, 2 => 80.0, 3 => 80.0, 4 => 50.0, 5 => 4.0,
            6 => 2.0, 7 => 1.0, 8 => 25.0, 9 => 18.0, 10 => 36.0,
            11 => 12.0, 12 => 4.0, 13 => 6.0, 14 => 100.0, 15 => 2.0,
            16 => 3.0, 17 => 15.0, 18 => 8.0, 19 => 5.0, 20 => 4.0,
            21 => 2.0, 22 => 10.0, 23 => 40.0, 24 => 6.0, 25 => 6.0,
        ];

        for ($i = 0; $i < 25; $i++) {
            if (isset($createdItems[$i])) {
                $itemId = $createdItems[$i]->id;
                RequirementList::firstOrCreate(
                    [
                        'requirement_id' => $req1->id,
                        'item_id' => $itemId,
                    ],
                    [
                        'quantity' => $quantities[$i + 1] ?? 5.0,
                    ]
                );
            }
        }

        // REQUERIMIENTO #2: Con 8 materiales
        $req2 = Requirement::firstOrCreate(
            [
                'sub_client_id' => $subClientLurin->id,
                'activity_name' => 'Instalación de Sistema Perimétrico y Reflectores en Bahía de Carga',
            ],
            [
                'created_by' => $user->id,
            ]
        );

        foreach (array_slice($createdItems, 8, 8) as $item) {
            RequirementList::firstOrCreate(
                ['requirement_id' => $req2->id, 'item_id' => $item->id],
                ['quantity' => 10.0]
            );
        }

        // REQUERIMIENTO #3: Con 5 materiales EPP
        $req3 = Requirement::firstOrCreate(
            [
                'sub_client_id' => $subClientSanIsidro->id,
                'activity_name' => 'Entrega Trimestral de Equipos de Protección Personal (EPP)',
            ],
            [
                'created_by' => $user->id,
            ]
        );

        foreach (array_slice($createdItems, 23, 7) as $item) {
            RequirementList::firstOrCreate(
                ['requirement_id' => $req3->id, 'item_id' => $item->id],
                ['quantity' => 4.0]
            );
        }

        $this->command?->info('');
        $this->command?->info('===============================================================');
        $this->command?->info('  SEMBRADO DE DATOS DE PRUEBA COMPLETADO EXITOSAMENTE');
        $this->command?->info('===============================================================');
        $this->command?->info('  Usuario demo:      admin@sat-industriales.pe / password');
        $this->command?->info('  Cliente:           SAT Industriales S.A.C.');
        $this->command?->info('  Tiendas / Sedes:   Planta Callao (ID: '.$subClientCallao->id.'), Lurín (ID: '.$subClientLurin->id.'), San Isidro (ID: '.$subClientSanIsidro->id.')');
        $this->command?->info('  Total Categorías:  '.count($categories));
        $this->command?->info('  Total Subcat.:     '.count($subcategories));
        $this->command?->info('  Total Ítems:       '.count($createdItems));
        $this->command?->info('  Requerimiento #1:  ID '.$req1->id.' (25 ítems - Ideal para probar paginación de 10 en 10)');
        $this->command?->info('  Requerimiento #2:  ID '.$req2->id.' (8 ítems)');
        $this->command?->info('  Requerimiento #3:  ID '.$req3->id.' (7 ítems)');
        $this->command?->info('===============================================================');
        $this->command?->info('  Endpoints listos para probar:');
        $this->command?->info('  - Paginación de ítems del req 1:  GET /api/requirements/'.$req1->id.'/items?per_page=10&page=1');
        $this->command?->info('  - Filtro por categoría Eléctricos: GET /api/requirements/'.$req1->id.'/items?category_id='.$categories['Materiales Eléctricos']->id);
        $this->command?->info('  - Búsqueda por SKU o nombre:      GET /api/requirements/'.$req1->id.'/items?search=cable');
        $this->command?->info('  - Catálogo de búsqueda general:   GET /api/items?category_id='.$categories['Ferretería y Estructuras']->id);
        $this->command?->info('===============================================================');
    }
}
