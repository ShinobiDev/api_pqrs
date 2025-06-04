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
            'guía' => 'PQRS001',
            'name' => 'Juan Pérez',
            'identification' => '1122334455',
            'phone' => '3001234567',
            'address' => 'Calle Falsa 123',
            'cel_phon' => '3011234567',
            'destination_city' => 'Bogotá',
            'pqrs_type_id' => 2, // Ej. tipo: PQR
            'description' => 'Queja sobre el servicio',
            'user_id' => 1, // Admin
        ]);
    }
}
