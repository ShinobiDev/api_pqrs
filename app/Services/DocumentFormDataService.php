<?php

namespace App\Services;

use App\Models\Type;
use App\Models\Status;
use App\Models\Role;

class DocumentFormDataService
{
    public function getFormData()
    {
        // Obtener tipos que tienen como padre "documentos"
        $tipos = Type::where('parent', 'documentos')->get();

        // Obtener todos los estados
        $estados = Status::all();

        // Obtener todos los roles
        $roles = Role::all();

        return [
            'tipos' => $tipos,
            'estados' => $estados,
            'roles' => $roles
        ];
    }
}