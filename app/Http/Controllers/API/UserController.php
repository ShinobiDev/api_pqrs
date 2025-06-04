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

    public function index()
    {
        return response()->json($this->service->index());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type_id' => 'required|integer|exists:types,id',
            'document' => 'required|string|max:50|unique:users,document',
            'role_id' => 'required|integer|exists:roles,id',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'status_id' => 'required|integer|exists:statuses,id',
            'password' => 'required|string|min:8', 
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

    public function update(Request $request, User $user)
    {
        try {
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
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
                'password' => 'nullable|string|min:8', // 'nullable' permite que el campo sea opcional
            ]);

            
            $dto = new EditUserDTO(
                $user->id, // Este es el ID del usuario que se va a actualizar
                $validated['name'],
                $validated['document_type_id'],
                $validated['document'],
                $validated['role_id'],
                $validated['email'],
                $validated['phone'],
                $validated['status_id'],
                $validated['password'] ?? null // Pasa null si la contraseña no se proporcionó
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