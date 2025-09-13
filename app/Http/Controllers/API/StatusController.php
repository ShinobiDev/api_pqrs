<?php

namespace App\Http\Controllers\API;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Services\StatusService;
use App\Http\Controllers\Controller;

use App\DTOs\Statuses\StatusDTO;
use App\DTOs\Statuses\EditStatusDTO;

class StatusController extends Controller
{
    protected $service;

    public function __construct(StatusService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/statuses",
     *     summary="Get all statuses",
     *     tags={"Statuses"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of statuses",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Active")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json($this->service->getAll());
    }

    /**
     * @OA\Post(
     *     path="/api/statuses",
     *     summary="Create a new status",
     *     tags={"Statuses"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Status created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dto = StatusDTO::fromArray($data);

        return response()->json($this->service->create($dto), 201);
    }

    public function update(Request $request, Status $status)
    {

        // Usamos $status->id directamente para la validación y el DTO
        $statusId = $status->id;

        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('statuses')->ignore($statusId, 'id'), 
            ],
        ]);
        try {
            // Crear el DTO DE EDICIÓN, pasándole el ID y el nombre
            $editStatusDTO = new EditStatusDTO($statusId, $validatedData['name']);

            // Llamar al servicio, que ahora esperará un EditStatusDTO
            $updatedStatus = $this->service->update($editStatusDTO);

            return response()->json([
                'message' => 'Estado actualizado exitosamente.',
                'status' => $updatedStatus->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estado no encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el estado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Status $status)
    {
        if ($status->trashed()) {
            return response()->json([
                'message' => 'El estado ya ha sido eliminado previamente.'
            ], 409); // 409 Conflict: indica que la solicitud no se pudo completar debido a un conflicto con el estado actual del recurso.
        }

        try {
            // Llama al servicio para realizar el soft delete
            $deleted = $this->service->delete($status);

            if ($deleted) {
                return response()->json([
                    'message' => 'Estado eliminado exitosamente (soft deleted).'
                ], 200); // 200 OK o 204 No Content, pero 200 con mensaje es más informativo
            } else {
                return response()->json([
                    'message' => 'No se pudo eliminar el estado.'
                ], 500); // 500 Internal Server Error si Laravel devuelve false inesperadamente
            }

        } catch (\Exception $e) {
            // Captura cualquier otra excepción que pueda ocurrir (ej. problemas de base de datos)
            return response()->json([
                'message' => 'Ocurrió un error al intentar eliminar el estado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaura un status previamente eliminado (soft deleted).
     *
     * @param string $id El ID del status a restaurar.
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(string $id)
    {
        try {
            $restoredStatus = $this->service->restore($id);

            return response()->json([
                'message' => 'Estado restaurado exitosamente.',
                'status' => $restoredStatus->toArray()
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estado no encontrado o ya restaurado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al restaurar el estado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina permanentemente un status.
     * Usar con precaución.
     *
     * @param string $id El ID del status a eliminar permanentemente.
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete(string $id)
    {
        try {
            $deleted = $this->service->forceDelete($id);

            if ($deleted) {
                return response()->json([
                    'message' => 'Estado eliminado permanentemente.'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No se pudo eliminar el estado permanentemente.'
                ], 500);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estado no encontrado para eliminación permanente.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el estado permanentemente.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
}
