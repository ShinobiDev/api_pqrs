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

    /**
     * @OA\Get(
     *     path="/api/pqrs",
     *     summary="Get all PQRS",
     *     tags={"PQRS"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of PQRS",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="guia", type="string", example="GU123456"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="document", type="string", example="123456789"),
     *                 @OA\Property(property="phone", type="string", example="3001234567"),
     *                 @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *                 @OA\Property(property="cel_phone", type="string", example="3009876543"),
     *                 @OA\Property(property="destiny_city_id", type="integer", example=1),
     *                 @OA\Property(property="pqrs_type_id", type="integer", example=1),
     *                 @OA\Property(property="description", type="string", example="Descripción del PQRS"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="status_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
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
     * @OA\Post(
     *     path="/api/pqrs",
     *     summary="Create a new PQRS",
     *     tags={"PQRS"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"guia","name","document","phone","address","cel_phone","destiny_city_id","pqrs_type_id","description","user_id","status_id"},
     *             @OA\Property(property="guia", type="string", example="GU123456"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="document", type="string", example="123456789"),
     *             @OA\Property(property="phone", type="string", example="3001234567"),
     *             @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *             @OA\Property(property="cel_phone", type="string", example="3009876543"),
     *             @OA\Property(property="destiny_city_id", type="integer", example=1),
     *             @OA\Property(property="pqrs_type_id", type="integer", example=1),
     *             @OA\Property(property="description", type="string", example="Descripción del PQRS"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="status_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="PQRS created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="PQRS creada exitosamente."),
     *             @OA\Property(property="pqrs", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
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

    /**
     * @OA\Put(
     *     path="/api/pqrs/{id}",
     *     summary="Update a PQRS",
     *     tags={"PQRS"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"guia","name","document","phone","address","cel_phone","destiny_city_id","pqrs_type_id","description","user_id","status_id"},
     *             @OA\Property(property="guia", type="string", example="GU123456"),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="document", type="string", example="123456789"),
     *             @OA\Property(property="phone", type="string", example="3001234567"),
     *             @OA\Property(property="address", type="string", example="Calle 123 #45-67"),
     *             @OA\Property(property="cel_phone", type="string", example="3009876543"),
     *             @OA\Property(property="destiny_city_id", type="integer", example=1),
     *             @OA\Property(property="pqrs_type_id", type="integer", example=1),
     *             @OA\Property(property="description", type="string", example="Descripción del PQRS"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="status_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PQRS updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="PQRS actualizada exitosamente."),
     *             @OA\Property(property="pqrs", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="PQRS not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/pqrs/{id}",
     *     summary="Delete a PQRS (soft delete)",
     *     tags={"PQRS"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PQRS deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="PQRS eliminada exitosamente (soft deleted).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="PQRS not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="PQRS already deleted"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
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
