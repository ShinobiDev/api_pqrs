<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'document_type_id' => 1, // ID de tipo "Cédula"
            'document' => '123456789',
            'role_id' => 1, // Admin
            'email' => 'admin@example.com',
            'phone' => '3110000000',
            'status_id' => 1, // Activo
            'password' => bcrypt('123456789'),
        ]);
    }
}
