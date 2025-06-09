<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pqrs;
use Illuminate\Http\Request;
use App\Services\PqrsService;
use App\DTOs\Pqrs\PqrsDTO;
use App\DTOs\Pqrs\EditPqrsDTO;

class PqrsController extends Controller
{
    public function __construct(PqrsService $service) 
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $pqrs = $this->service->index();
            return response()->json($pqrs, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al obtener las PQRS.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validación de los datos de entrada
            $validated = $request->validate([
                'guia' => 'required|string',
                'name' => 'required|string',
                'document' => 'required|string',
                'phone' => 'required|string',
                'address' => 'required|string',
                'cel_phone' => 'required|string',
                'destiny_city_id' => 'required|integer|exists:cities,id',
                'pqrs_type_id' => 'required|integer|exists:types,id',
                'description' => 'required|string',
                'user_id' => 'required|integer|exists:users,id',
                'status_id' => 'required|integer|exists:statuses,id',
            ]);

            // Crear el DTO
            $dto = new PqrsDTO(
                $validated['guia'],
                $validated['name'],
                $validated['document'],
                $validated['phone'],
                $validated['address'],
                $validated['cel_phone'],
                $validated['destiny_city_id'],
                $validated['pqrs_type_id'],
                $validated['description'],
                $validated['user_id'],
                $validated['status_id']
            );

            // Llamar al servicio para crear la PQRS
            $pqrs = $this->service->store($dto);

            // Respuesta de éxito
            return response()->json([
                'message' => 'PQRS creada exitosamente.',
                'pqrs' => $pqrs->toArray()
            ], 201); // 201 Created

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos para la creación de la PQRS.',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar crear la PQRS.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    public function update(Request $request, Pqrs $pqrs)
    {
        $pqrsId = $pqrs->id;

        $validated = $request->validate([
            'guia' => 'required|string',
            'name' => 'required|string',
            'document' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'cel_phone' => 'required|string',
            'destiny_city_id' => 'required|integer|exists:cities,id',
            'pqrs_type_id' => 'required|integer|integer|exists:types,id',
            'description' => 'required|string',
            'user_id' => 'required|integer|integer|exists:users,id',
            'status_id' => 'required|integer|exists:statuses,id',
        ]);
        try {
            $dto = new EditPqrsDTO(
                $pqrsId, // Pass the Pqrs ID
                $validated['guia'],
                $validated['name'], 
                $validated['document'],
                $validated['phone'],
                $validated['address'],
                $validated['cel_phone'],
                $validated['destiny_city_id'],
                $validated['pqrs_type_id'],
                $validated['description'],
                $validated['user_id'],
                $validated['status_id']
            );
            $updated = $this->service->update($dto);

            return response()->json([
                'message' => 'PQRS actualizada exitosamente.',
                'pqrs' => $updated->toArray()
            ], 200); 
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PQRS no encontrada.'
            ], 404); // 404 Not Found
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos para la actualización de la PQRS.',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar actualizar la PQRS.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    public function destroy(Pqrs $pqrs)
    {
        try {
            // Verificar si la PQRS ya ha sido eliminada (soft deleted)
            if ($pqrs->trashed()) {
                return response()->json([
                    'message' => 'La PQRS ya ha sido eliminada previamente.'
                ], 409); // 409 Conflict
            }

            // Llamar al servicio para realizar el soft delete
            $deleted = $this->service->destroy($pqrs);

            if ($deleted) {
                return response()->json([
                    'message' => 'PQRS eliminada exitosamente (soft deleted).'
                ], 200); // 200 OK
            } else {
                return response()->json([
                    'message' => 'No se pudo eliminar la PQRS.'
                ], 500);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'PQRS no encontrada.'
            ], 404); // 404 Not Found
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar eliminar la PQRS.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }
}
