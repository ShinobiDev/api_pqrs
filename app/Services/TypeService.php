<?php

namespace App\Services;

use App\Models\Type;
use App\DTOs\Types\TypeDTO;

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

    public function update(Type $type, TypeDTO $dto): Type
    {
        $type->update([
            'name' => $dto->name,
            'parent_type_id' => $dto->parent_type_id,
        ]);
        return $type;
    }

    public function delete(Type $type): void
    {
        $type->delete();
    }
}
