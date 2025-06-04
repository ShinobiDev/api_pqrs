<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;

use App\Models\Role;
use App\DTOs\Roles\RoleDTO;
use App\Services\RoleService;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    protected $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->getAll());
    }

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
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dto = RoleDTO::fromArray($data);

        return response()->json($this->service->update($role, $dto));
    }

    public function destroy(Role $role)
    {
        $this->service->delete($role);
        return response()->json(null, 204);
    }
}
