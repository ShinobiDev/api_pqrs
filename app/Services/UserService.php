<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\Users\UserDTO;
use App\DTOs\Users\EditUserDTO;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index()
    {
        return User::all();
    }

    public function store(UserDTO $dto): User
    {
        try {
            
            $hashedPassword = Hash::make($dto->password);

            $user = User::create([
                'name' => $dto->name,
                'document_type_id' => $dto->document_type_id,
                'document' => $dto->document,
                'role_id' => $dto->role_id,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'status_id' => $dto->status_id,
                'password' => $hashedPassword, 
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
        $user = User::findOrFail($dto->id); // Ahora $dto->id existirá
        $user->update([
            'name' => $dto->name,
            'document_type_id' => $dto->document_type_id,
            'document' => $dto->document,
            'role_id' => $dto->role_id,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'status_id' => $dto->status_id,
        ]);

        return $user;
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
