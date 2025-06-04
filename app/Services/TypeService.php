<?php

namespace App\Services;

use App\Models\Type;
use App\DTOs\Types\TypeDTO;
use App\DTOs\Types\EditTypeDTO;

class TypeService
{
    public function getAll()
    {
        return Type::all();
    }

    public function create(TypeDTO $dto): Type
    {
        return Type::create([
            'name' => $dto->name,
            'parent_type_id' => $dto->parent_type_id,
        ]);
    }

    public function update(EditTypeDTO $dto): Type // <--- ¡Cambia el tipo aquí!
    {
        $type = Type::findOrFail($dto->id); // Ahora $dto->id existirá
        $type->update(['name' => $dto->name]);
        return $type;
    }

    public function delete(Type $type): void
    {
        $type->delete();
    }
}
