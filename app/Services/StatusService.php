<?php

namespace App\Services;

use App\Models\Status;
use App\DTOs\Statuses\StatusDTO;
use App\DTOs\Statuses\EditStatusDTO;

class StatusService
{
    public function getAll()
    {
        return Status::all();
    }

    public function create(StatusDTO $dto): Status
    {
        return Status::create(['name' => $dto->name]);
    }

    public function update(EditStatusDTO $dto): Status // <--- ¡Cambia el tipo aquí!
    {
        $status = Status::findOrFail($dto->id); // Ahora $dto->id existirá
        $status->update(['name' => $dto->name]);
        return $status;
    }

    public function delete(Status $status): bool
    {
        return $status->delete();
    }

    /**
     * Restaura un status que fue soft-deleted.
     * Puedes añadir este método si necesitas una funcionalidad de "restaurar".
     *
     * @param string $id El ID del status a restaurar.
     * @return Status El modelo Status restaurado.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el status no se encuentra (incluyendo los eliminados).
     */
    public function restore(string $id): Status
    {
        // withTrashed() permite buscar también en los registros eliminados suavemente.
        $status = Status::withTrashed()->findOrFail($id);
        $status->restore(); // Restaura el registro (pone deleted_at en null)
        return $status;
    }

    /**
     * Elimina permanentemente un status (hard delete).
     * Úsalo con precaución.
     *
     * @param string $id El ID del status a eliminar permanentemente.
     * @return bool True si se eliminó permanentemente exitosamente.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el status no se encuentra (incluyendo los eliminados).
     */
    public function forceDelete(string $id): bool
    {
        // withTrashed() para asegurar que podemos eliminar incluso si ya está soft-deleted.
        $status = Status::withTrashed()->findOrFail($id);
        return $status->forceDelete(); // Elimina permanentemente
    }
}
