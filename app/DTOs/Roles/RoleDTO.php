<?php

namespace App\DTOs\Roles;

class RoleDTO
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['name']);
    }
}
