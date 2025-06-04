<?php

namespace App\Services;

use App\Models\Role;
use App\DTOs\Roles\RoleDTO;

class RoleService
{
    public function getAll()
    {
        return Role::all();
    }

    public function create(RoleDTO $dto): Role
    {
        return Role::create(['name' => $dto->name]);
    }

    public function update(Role $role, RoleDTO $dto): Role
    {
        $role->update(['name' => $dto->name]);
        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
