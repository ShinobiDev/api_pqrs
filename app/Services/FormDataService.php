<?php

namespace App\Services;

use App\Models\Type;
use App\Models\Status;

class FormDataService
{
    /**
     * Obtiene los datos necesarios para el formulario de creación de cliente
     */
    public function getClientFormData()
    {
        return Type::where('parent_type_id', 1)
                 ->select('id', 'name')
                 ->get()
                 ->toArray();
    }
}