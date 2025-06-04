<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\DTOs\Users\UserDTO;

class UserController extends Controller
{
    public function __construct(UserService $service) {}

    public function index()
    {
        return response()->json($this->service->index());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'document_type_id' => 'required|integer',
            'document' => 'required|string',
            'role_id' => 'required|integer',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
            'status_id' => 'required|integer',
        ]);

        $dto = UserDTO::fromArray($validated);
        $user = $this->service->store($dto);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'document_type_id' => 'required|integer',
            'document' => 'required|string',
            'role_id' => 'required|integer',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string',
            'status_id' => 'required|integer',
        ]);

        $dto = UserDTO::fromArray($validated);
        $updatedUser = $this->service->update($user, $dto);

        return response()->json($updatedUser);
    }

    public function destroy(User $user)
    {
        $this->service->destroy($user);
        return response()->json(['message' => 'User deleted']);
    }
}