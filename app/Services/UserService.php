<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\Users\UserDTO;
use App\DTOs\Users\EditUserDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function index()
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])->get();
    }

    public function getClientUsers()
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->where('role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('name', 'like', '%client%')
                    ->orWhere('name', 'like', '%Client%')
                    ->first();
            })
            ->get();
    }

    public function show(User $user)
    {
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->findOrFail($user->id);
    }

    public function store(UserDTO $dto): User
    {
        try {
            $hashedPassword = Hash::make($dto->password);
            
            $user = User::create([
                'user_type_id' => $dto->user_type_id,
                'name' => $dto->name,
                'document_type_id' => $dto->document_type_id,
                'document' => $dto->document,
                'role_id' => $dto->role_id,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'status_id' => $dto->status_id,
                'password' => $hashedPassword,
                'client_id' => $dto->client_id,
            ]);

            return $user;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error de base de datos al crear el usuario: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \Exception('Ocurrió un error inesperado al crear el usuario: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(EditUserDTO $dto): User
    {
        $user = User::findOrFail($dto->id);

        $updateData = [
            'name' => $dto->name,
            'user_type_id' => $dto->user_type_id,
            'document_type_id' => $dto->document_type_id,
            'document' => $dto->document,
            'role_id' => $dto->role_id,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'status_id' => $dto->status_id,
            'client_id' => $dto->client_id,
        ];

        // Solo actualizar la contraseña si se proporciona una nueva
        if (!empty($dto->password)) {
            $updateData['password'] = Hash::make($dto->password);
        }

        $user->update($updateData);

        // Refrescar el modelo con todas las relaciones
        return User::with(['type', 'client', 'documentType', 'role', 'status'])
            ->findOrFail($user->id);
    }

    public function destroy(User $user): bool
    {
        try {
            return $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error de base de datos al eliminar el usuario: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new \Exception('Ocurrió un error inesperado al eliminar el usuario: ' . $e->getMessage(), 0, $e);
        }
    }
}
