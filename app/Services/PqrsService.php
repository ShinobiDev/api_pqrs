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

    /**
     * Crea una nueva PQRS.
     *
     * @param PqrsDTO $dto
     * @return Pqrs
     * @throws \Exception
     */
    public function store(PqrsDTO $dto): Pqrs
    {
        try {
            $pqrs = Pqrs::create([
                'guia' => $dto->guia,
                'name' => $dto->name,
                'document' => $dto->document,
                'phone' => $dto->phone,
                'address' => $dto->address,
                'cel_phone' => $dto->cel_phone,
                'destiny_city_id' => $dto->destiny_city_id,
                'pqrs_type_id' => $dto->pqrs_type_id,
                'description' => $dto->description,
                'user_id' => $dto->user_id,
                'status_id' => $dto->status_id,
            ]);
            return $pqrs;
        } catch (\Exception $e) {
            throw new \Exception('Error al crear la PQRS: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(PqrsDTO $dto)
    {
        try {
            $pqrs = Pqrs::findOrFail($dto->id);
            $pqrs->update([
                'guia' => $dto->guia,
                'name' => $dto->name,
                'document' => $dto->document,
                'phone' => $dto->phone,
                'address' => $dto->address,
                'cel_phone' => $dto->cel_phone,
                'destiny_city_id' => $dto->destiny_city_id,
                'pqrs_type_id' => $dto->pqrs_type_id,
                'description' => $dto->description,
                'user_id' => $dto->user_id,
                'status_id' => $dto->status_id,
            ]);
            return $pqrs;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('PQRS con ID ' . $dto->id . ' no encontrada.');
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar la PQRS: ' . $e->getMessage(), 0, $e);
        }
    }

    public function destroy(Pqrs $pqrs)
    {
        try {
            // Asegúrate de que el modelo Pqrs use el trait SoftDeletes.
            return $pqrs->delete();
        } catch (\Exception $e) {
            throw new \Exception('Error al eliminar la PQRS: ' . $e->getMessage(), 0, $e);
        }
    }
}
