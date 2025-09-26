<?php

namespace App\Services;

use App\Models\Type;
use App\Models\Status;
use App\Models\Role;

class UserFormDataService
{
    public function getFormData()
    {
        // Obtener tipos de documento (los que tienen parent_type_id = 1)
        $tipos = Type::where('parent_type_id', 1)
            ->select('id', 'name')
            ->get();

        // Obtener todos los estados
        $estados = Status::select('id', 'name')->get();

        // Obtener todos los roles
        $roles = Role::select('id', 'name')->get();

        return [
            'tipos' => $tipos,
            'estados' => $estados,
            'roles' => $roles
        ];
    }
}
