<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Models\Role;
use App\DTOs\Roles\RoleDTO;
use App\DTOs\Roles\EditRoleDTO;
use App\Services\RoleService;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    protected $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get all roles",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of roles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="description", type="string", example="Administrator role")
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
     *     path="/api/roles",
     *     summary="Create a new role",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", example="Manager"),
     *             @OA\Property(property="description", type="string", example="Manager role description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dto = RoleDTO::fromArray($data);

        return response()->json($this->service->create($dto), 201);
    }

    public function update(Request $request, Role $role)
    {
        $roleId = $role->id;

        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('roles')->ignore($roleId, 'id'), 
            ],
        ]);

        try {
            // Crear el DTO DE EDICIÓN, pasándole el ID y el nombre
            $editRoleDTO = new EditRoleDTO($roleId, $validatedData['name']);

            // Llamar al servicio, que ahora esperará un EditRoleDTO
            $updatedRole = $this->service->update($editRoleDTO);

            return response()->json([
                'message' => 'Rol actualizado exitosamente.',
                'status' => $updatedRole->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Rol no encontrado.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el rol.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Role $role)
    {
        $this->service->delete($role);
        return response()->json(null, 204);
    }
}
