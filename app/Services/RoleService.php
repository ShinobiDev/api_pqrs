<?php

namespace App\Services;

use App\Models\Role;
use App\DTOs\Roles\RoleDTO;
use App\DTOs\Roles\EditRoleDTO;

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

    public function update(EditRoleDTO $dto): Role // <--- ¡Cambia el tipo aquí!
    {
        $role = Role::findOrFail($dto->id); // Ahora $dto->id existirá
        $role->update(['name' => $dto->name]);
        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
