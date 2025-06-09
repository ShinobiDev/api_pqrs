<?php

namespace Database\Seeders;

use App\Models\Pqrs;
use Illuminate\Database\Seeder;

class PqrsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Pqrs::create([
            'guia' => 'PQRS001',
            'name' => 'Juan Pérez',
            'document' => '1122334455',
            'phone' => '3001234567',
            'address' => 'Calle Falsa 123',
            'cel_phone' => '3011234567',
            'destiny_city_id' => 1,
            'pqrs_type_id' => 2, // Ej. tipo: PQR
            'description' => 'Queja sobre el servicio',
            'user_id' => 1, // Admin
            'status_id' => 1
        ]);
    }
}
