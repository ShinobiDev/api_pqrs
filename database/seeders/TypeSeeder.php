<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Tipos principales
        $document = Type::create(['name' => 'Documentos', 'parent_type_id' => null]);
        $pqrs = Type::create(['name' => 'Pqrs', 'parent_type_id' => null]);
        $user = Type::create(['name' => 'Usuarios', 'parent_type_id' => null]);

        // Tipos hijos
        Type::insert([
            ['name' => 'Cédula de Ciudadanía', 'parent_type_id' => $document->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tarjeta de Identidad', 'parent_type_id' => $document->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cédula de Extranjería', 'parent_type_id' => $document->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Passaporte', 'parent_type_id' => $document->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'NIT', 'parent_type_id' => $document->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Type::insert([
            ['name' => 'Administrador', 'parent_type_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cliente', 'parent_type_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Usuario', 'parent_type_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Type::insert([
            ['name' => 'Entrega Retrasada', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Entrega Defectuosa', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Entrega Incompleta', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mala Presentación', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Descortesía', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Horarios Inapropiados', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Requerimiento informático', 'parent_type_id' => $pqrs->id, 'created_at' => now(), 'updated_at' => now()]
        ]);
    }
}
