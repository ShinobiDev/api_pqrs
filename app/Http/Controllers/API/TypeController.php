<?php

namespace App\Http\Controllers\API;

use App\Models\Type;

use App\DTOs\types\TypeDTO;
use App\DTOs\types\EditTypeDTO;

use App\Services\TypeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class TypeController extends Controller
{
    protected $service;

    public function __construct(TypeService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/types",
     *     summary="Get all types",
     *     tags={"Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of types",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Petición"),
     *                 @OA\Property(property="parent_type_id", type="integer", nullable=true, example=null)
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
     * @OA\Get(
     *     path="/api/types/documents",
     *     summary="Get all document types",
     *     tags={"Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of document types",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Cédula de Ciudadanía")
     *             )
     *         )
     *     )
     * )
     */
    public function getDocumentTypes()
    {
        return response()->json($this->service->getDocumentTypes());
    }

    /**
     * @OA\Post(
     *     path="/api/types",
     *     summary="Create a new type",
     *     tags={"Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Queja"),
     *             @OA\Property(property="parent_type_id", type="integer", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Type created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_type_id' => 'nullable|integer|exists:types,id',
        ]);

        $dto = TypeDTO::fromArray($data);

        return response()->json($this->service->create($dto), 201);
    }

    public function update(Request $request, Type $type)
    {
        $typeId = $type->id;

        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('types')->ignore($typeId, 'id'), 
            ],
        ]);

        try {
            // Crear el DTO DE EDICIÓN, pasándole el ID y el nombre
            $editTypeDTO = new EditTypeDTO($typeId, $validatedData['name']);

            // Llamar al servicio, que ahora esperará un EditTypeDTO
            $updatedType = $this->service->update($editTypeDTO);

            return response()->json([
                'message' => 'Tipo actualizado exitosamente.',
                'status' => $updatedType->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tipo no encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el tipo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Type $type)
    {
        $this->service->delete($type);
        return response()->json(null, 204);
    }
}
