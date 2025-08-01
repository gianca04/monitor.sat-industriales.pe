<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llamar a los seeders existentes
        $this->call([
            ClientSeeder::class,
            SubClientSeeder::class,
            ClientesSSeeder::class,
            EmployeeSeeder::class,
            ProyectosSeeder::class,
        ]);

    }
}
