<?php

namespace App\Services;

use App\Models\User;
use App\DTOs\Users\UserDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ClientBatchService
{
    public function createClientWithUsers(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear el usuario cliente principal
            $clientData = $data['client'];
            $clientUser = $this->createUser($clientData, null);

            $createdUsers = [$clientUser];

            // 2. Crear los usuarios adicionales asociados al cliente
            if (isset($data['users']) && is_array($data['users'])) {
                foreach ($data['users'] as $userData) {
                    $user = $this->createUser($userData, $clientUser->id);
                    $createdUsers[] = $user;
                }
            }

            // 3. Cargar las relaciones para todos los usuarios creados
            $userIds = collect($createdUsers)->pluck('id');
            $usersWithRelations = User::with(['type', 'client', 'documentType', 'role', 'status'])
                ->whereIn('id', $userIds)
                ->get();

            return [
                'client' => $usersWithRelations->where('id', $clientUser->id)->first(),
                'users' => $usersWithRelations->where('id', '!=', $clientUser->id)->values(),
                'total_created' => count($createdUsers)
            ];
        });
    }

    private function createUser(array $userData, $clientId = null)
    {
        $hashedPassword = Hash::make($userData['password']);

        return User::create([
            'user_type_id' => $userData['user_type_id'],
            'name' => $userData['name'],
            'document_type_id' => $userData['document_type_id'],
            'document' => $userData['document'],
            'role_id' => $userData['role_id'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'status_id' => $userData['status_id'],
            'password' => $hashedPassword,
            'client_id' => $clientId,
        ]);
    }
}