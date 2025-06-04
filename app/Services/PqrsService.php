<?php

namespace App\Services;

use App\Models\Pqrs;
use App\DTOs\Pqrs\PqrsDTO;

class PqrsService
{
    public function index()
    {
        return Pqrs::all();
    }

    public function store(PqrsDTO $dto)
    {
        return Pqrs::create([
            'guia' => $dto->guia,
            'name' => $dto->name,
            'identification' => $dto->identification,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'cel_phon' => $dto->cel_phon,
            'destination_city' => $dto->destination_city,
            'pqrs_type_id' => $dto->pqrs_type_id,
            'description' => $dto->description,
            'user_id' => $dto->user_id,
        ]);
    }

    public function update(Pqrs $pqrs, PqrsDTO $dto)
    {
        $pqrs->update([
            'guia' => $dto->guia,
            'name' => $dto->name,
            'identification' => $dto->identification,
            'phone' => $dto->phone,
            'address' => $dto->address,
            'cel_phon' => $dto->cel_phon,
            'destination_city' => $dto->destination_city,
            'pqrs_type_id' => $dto->pqrs_type_id,
            'description' => $dto->description,
            'user_id' => $dto->user_id,
        ]);

        return $pqrs;
    }

    public function destroy(Pqrs $pqrs)
    {
        $pqrs->delete();
    }
}
