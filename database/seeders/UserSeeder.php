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
            'user_type_id' => 6, // ID de tipo "Administrador"
            'client_id' => null,
            'name' => 'Adminastrador Principal',
            'document_type_id' => 1, // ID de tipo "Cédula"
            'document' => '123456789',
            'role_id' => 1, // Super Admin
            'email' => 'admin@example.com',
            'phone' => '3110000000',
            'status_id' => 1, // Activo
            'password' => bcrypt('123456789'),
        ]);

        User::create([
            'user_type_id' => 7, // ID de tipo "Cliente"
            'client_id' => null,
            'name' => 'Conjunto Residencial Arrayan',
            'document_type_id' => 5, // ID de tipo "NIT"
            'document' => '1122334455',
            'role_id' => 4, // Cliente
            'email' => 'conjuntoarrayan@example.com',
            'phone' => '3112223344',
            'status_id' => 1, // Activo
            'password' => bcrypt('123456789'),
        ]);

        User::create([
            'user_type_id' => 8, // ID de tipo "Usuario"
            'client_id' => null,
            'name' => 'Admin Arrayan',
            'document_type_id' => 1, // ID de tipo "Cédula"
            'document' => '6677889900',
            'role_id' => 2, // Administrador
            'email' => 'adminayan@example.com',
            'phone' => '3203278911',
            'status_id' => 1, // Activo
            'password' => bcrypt('123456789'),
        ]);

        User::create([
            'user_type_id' => 8, // ID de tipo "Usuario"
            'client_id' => null,
            'name' => 'Usuario 1 Arrayan',
            'document_type_id' => 1, // ID de tipo "Cédula"
            'document' => '80793167',
            'role_id' => 2, // Usuario
            'email' => 'usuario1@example.com',
            'phone' => '3132776404',
            'status_id' => 1, // Activo
            'password' => bcrypt('123456789'),
        ]);

        User::create([
            'user_type_id' => 8, // ID de tipo "Usuario"
            'client_id' => null,
            'name' => 'Usuario 2 Arrayan',
            'document_type_id' => 1, // ID de tipo "Cédula"
            'document' => '807931670',
            'role_id' => 2, // Usuario
            'email' => 'usuario2@example.com',
            'phone' => '3114461157',
            'status_id' => 1, // Activo
            'password' => bcrypt('123456789'),
        ]);
    }
}
