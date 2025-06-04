<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::insert([
            ['name' => 'Activo', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Inactivo', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
