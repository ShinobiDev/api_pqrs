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
}
