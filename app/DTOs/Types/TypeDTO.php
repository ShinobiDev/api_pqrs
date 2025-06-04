<?php

namespace App\DTOs\Types;

class TypeDTO
{
    public $name;
    public $parent_type_id;

    public function __construct(string $name, $parent_type_id = null)
    {
        $this->name = $name;
        $this->parent_type_id = $parent_type_id;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['parent_type_id'] ?? null
        );
    }
}
