<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\DTOs\Users\UserDTO;
use App\DTOs\Users\EditUserDTO;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="document", type="string", example="123456789"),
     *                 @OA\Property(property="phone", type="string", example="3001234567"),
     *                 @OA\Property(property="role_id", type="integer", example=1),
     *                 @OA\Property(property="status_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        return response()->json($this->service->index());
    }

    /**
     * @OA\Get(
     *     path="/api/users/clients",
     *     summary="Get all client users",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of client users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getClientUsers()
    {
        return response()->json($this->service->getClientUsers());
    }

    /**
     * @OA\Get(
     *     path="/api/users/client/{clientId}",
     *     summary="Get all users by client ID",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID del cliente"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of users for the specified client",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     )
     * )
     */
    public function getUsersByClient($clientId)
    {
        try {
            $users = $this->service->getUsersByClient($clientId);
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios del cliente obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios del cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/clients/active",
     *     summary="Get all active client users",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active client users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="message", type="string", example="Usuarios clientes activos obtenidos exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getActiveClientUsers()
    {
        try {
            $users = $this->service->getActiveClientUsers();
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios clientes activos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios clientes activos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/clients/inactive",
     *     summary="Get all inactive client users",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of inactive client users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="message", type="string", example="Usuarios clientes inactivos obtenidos exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getInactiveClientUsers()
    {
        try {
            $users = $this->service->getInactiveClientUsers();
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios clientes inactivos obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios clientes inactivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/clients/deleted",
     *     summary="Get all deleted client users (soft deleted)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of deleted client users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *             @OA\Property(property="message", type="string", example="Usuarios clientes eliminados obtenidos exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getDeletedClientUsers()
    {
        try {
            $users = $this->service->getDeletedClientUsers();
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios clientes eliminados obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios clientes eliminados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/clients/all",
     *     summary="Get all client users (active, inactive and deleted)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all client users including deleted ones",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="active",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/User")
     *                 ),
     *                 @OA\Property(
     *                     property="inactive",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/User")
     *                 ),
     *                 @OA\Property(
     *                     property="deleted",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/User")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=10)
     *             ),
     *             @OA\Property(property="message", type="string", example="Todos los usuarios clientes obtenidos exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getAllClientUsers()
    {
        try {
            $allUsers = $this->service->getAllClientUsers();
            
            // Separar usuarios por estado
            $active = $allUsers->where('status_id', 1)->whereNull('deleted_at')->values();
            $inactive = $allUsers->where('status_id', '!=', 1)->whereNull('deleted_at')->values();
            $deleted = $allUsers->whereNotNull('deleted_at')->values();
            
            $response = [
                'active' => $active,
                'inactive' => $inactive,
                'deleted' => $deleted,
                'total' => $allUsers->count(),
                'counts' => [
                    'active' => $active->count(),
                    'inactive' => $inactive->count(),
                    'deleted' => $deleted->count()
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Todos los usuarios clientes obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener todos los usuarios clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user details",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show(User $user)
    {
        return response()->json($this->service->show($user));
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","document_type_id","document","role_id","email","phone","status_id","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="document_type_id", type="integer", example=1),
     *             @OA\Property(property="document", type="string", example="123456789"),
     *             @OA\Property(property="role_id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="3001234567"),
     *             @OA\Property(property="status_id", type="integer", example=1),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario creado exitosamente."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_type_id' => 'required|integer|exists:types,id',
            'document_type_id' => 'required|integer|exists:types,id',
            'document' => 'required|string|max:50|unique:users,document',
            'role_id' => 'required|integer|exists:roles,id',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'status_id' => 'required|integer|exists:statuses,id',
            'password' => 'required|string|min:8',
            'client_id' => 'nullable|integer|exists:users,id',
        ]);

        try {

            $dto = UserDTO::fromArray($validated);
            $user = $this->service->store($dto);

            return response()->json([
                'message' => 'Usuario creado exitosamente.',
                'user' => $user->toArray()
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Ocurrió un error al intentar crear el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update a user",
     *     tags={"Users"},
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
     *             required={"name","document_type_id","document","role_id","email","phone","status_id"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="document_type_id", type="integer", example=1),
     *             @OA\Property(property="document", type="string", example="123456789"),
     *             @OA\Property(property="role_id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="3001234567"),
     *             @OA\Property(property="status_id", type="integer", example=1),
     *             @OA\Property(property="password", type="string", minLength=8, example="newpassword123", description="Optional - leave empty to keep current password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario actualizado exitosamente."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, User $user)
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'user_type_id' => 'required|integer|exists:types,id',
                'document_type_id' => 'required|integer|exists:types,id',
                'document' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('users', 'document')->ignore($user->id),
                ],
                'role_id' => 'required|integer|exists:roles,id',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'phone' => 'required|string|max:20',
                'status_id' => 'required|integer|exists:statuses,id',
                'password' => 'nullable|string|min:8',
                'client_id' => 'nullable|integer|exists:users,id',
            ]);


            $dto = new EditUserDTO(
                $user->id,
                $validated['name'],
                $validated['user_type_id'],
                $validated['document_type_id'],
                $validated['document'],
                $validated['role_id'],
                $validated['email'],
                $validated['phone'],
                $validated['status_id'],
                $validated['password'] ?? null,
                $validated['client_id'] ?? null
            );

            $updatedUser = $this->service->update($dto);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente.',
                'user' => $updatedUser->toArray()
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos para la actualización.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar actualizar el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete a user (soft delete)",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario eliminado exitosamente (soft deleted).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="User already deleted"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function destroy(User $user)
    {
        try {
            if ($user->trashed()) {
                return response()->json([
                    'message' => 'El usuario ya ha sido eliminado previamente.'
                ], 409);
            }

            $deleted = $this->service->destroy($user);

            if ($deleted) {
                return response()->json([
                    'message' => 'Usuario eliminado exitosamente (soft deleted).'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No se pudo eliminar el usuario.'
                ], 500);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], 404);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Ocurrió un error al intentar eliminar el usuario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/clients/export",
     *     summary="Export clients to Excel",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="include_deleted",
     *         in="query",
     *         description="Include deleted clients in export",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel file download",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error exporting clients"
     *     )
     * )
     */
    public function exportClients(Request $request)
    {
        try {
            $includeDeleted = $request->boolean('include_deleted', false);
            
            return $this->service->exportClients($includeDeleted);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al exportar los clientes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
