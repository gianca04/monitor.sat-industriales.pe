<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ClientesSSeeder extends Seeder
{
    public function run()
    {
        // Clientes
        $clientes = [
            [
                'id' => 150,
                'person_type' => 'JURIDICA',
                'document_type' => 'RUC',
                'document_number' => '20603281994',
                'business_name' => 'EMERGENT COLD PERU S.A.C.',
                'contact_email' => 'INFO@EMERGENTCOLD.COM',
                'address' => 'MZA. C, LOTE 3, Z.I., PARQUE INDUSTRIAL PIURA FUTURA (ETAPA II), PIURA, PIURA',
                'contact_phone' => '',
                'description' => '',
                'created_at' => '2025-06-03',
                'updated_at' => '2025-06-03',
            ],
            [
                'id' => 151,
                'person_type' => 'JURIDICA',
                'document_type' => 'RUC',
                'document_number' => '20487419592',
                'business_name' => 'SDN - SALES DEL NORTE S.A.C.',
                'contact_email' => 'BCARMELA144@GMAIL.COM',
                'address' => 'AR. CHICLAYO-SAN JOSE KM. 3.5 FND. EL HIGO (PORTON AZUL, ANTES DE CIUDAD DE DIOS)',
                'contact_phone' => '956315475',
                'description' => '',
                'created_at' => '2025-06-19',
                'updated_at' => '2025-06-19',
            ],
            [
                'id' => 152,
                'person_type' => 'JURIDICA',
                'document_type' => 'RUC',
                'document_number' => '20100176450',
                'business_name' => 'SOLGAS S.A.',
                'contact_email' => '',
                'address' => 'VILLA EL SALVADOR SECTOR 2, LIMA',
                'contact_phone' => '',
                'description' => '',
                'created_at' => '2025-07-08',
                'updated_at' => '2025-07-08',
            ],
            [
                'id' => 162,
                'person_type' => 'JURIDICA',
                'document_type' => 'RUC',
                'document_number' => '20609968894',
                'business_name' => 'INSPIRATUS TECHNOLOGIES S.A.C.',
                'contact_email' => '',
                'address' => 'CAL. LOS NARANJOS NRO. 329',
                'contact_phone' => '897979999',
                'description' => '',
                'created_at' => '2025-07-11',
                'updated_at' => '2025-07-11',
            ],
            [
                'id' => 163,
                'person_type' => 'JURIDICA',
                'document_type' => 'RUC',
                'document_number' => '20609041219',
                'business_name' => 'GM OPERACIONES SAC',
                'contact_email' => '',
                'address' => 'TALARA',
                'contact_phone' => '',
                'description' => '',
                'created_at' => '2025-07-23',
                'updated_at' => '2025-07-23',
            ],
        ];
        DB::table('clients')->upsert($clientes, ['id'], [
            'person_type','document_type','document_number','business_name','contact_email','address','contact_phone','description','created_at','updated_at'
        ]);

        // SubClientes
        $subclientes = [
            ['id' => 177, 'name' => 'EMERGENT COLD', 'client_id' => 150, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-06-03', 'updated_at' => '2025-06-03'],
            ['id' => 178, 'name' => 'CHICLAYO - PIMENTEL', 'client_id' => 151, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-06-19', 'updated_at' => '2025-06-19'],
            ['id' => 179, 'name' => 'SND - CHICLAYO', 'client_id' => 151, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-06-19', 'updated_at' => '2025-06-19'],
            ['id' => 180, 'name' => 'SDN - CHICLAYO', 'client_id' => 151, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-06-19', 'updated_at' => '2025-06-19'],
            ['id' => 181, 'name' => 'SOLGAS', 'client_id' => 152, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-07-08', 'updated_at' => '2025-07-08'],
            ['id' => 182, 'name' => 'INSPIRATUS', 'client_id' => 162, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-07-11', 'updated_at' => '2025-07-11'],
            ['id' => 183, 'name' => 'TALARA - GM', 'client_id' => 163, 'description' => null, 'location' => null, 'latitude' => null, 'longitude' => null, 'created_at' => '2025-07-23', 'updated_at' => '2025-07-23'],
        ];
        DB::table('sub_clients')->upsert($subclientes, ['id'], [
            'name','client_id','description','location','latitude','longitude','created_at','updated_at'
        ]);
    }
}
