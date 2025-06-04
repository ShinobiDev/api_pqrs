<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\Users\UserDTO;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index()
    {
        return User::all();
    }

    public function store(UserDTO $dto)
    {
        return User::create([
            'name' => $dto->name,
            'document_type_id' => $dto->document_type_id,
            'document' => $dto->document,
            'role_id' => $dto->role_id,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'status_id' => $dto->status_id,
            'password' => Hash::make('password'), // Password temporal
        ]);
    }

    public function update(User $user, UserDTO $dto)
    {
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

    public function destroy(User $user)
    {
        $user->delete();
    }
}
