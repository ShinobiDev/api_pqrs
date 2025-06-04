<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\TypeService;
use App\DTOs\types\TypeDTO;
use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    protected $service;

    public function __construct(TypeService $service)
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
            'parent_type_id' => 'nullable|integer|exists:types,id',
        ]);

        $dto = TypeDTO::fromArray($data);

        return response()->json($this->service->create($dto), 201);
    }

    public function update(Request $request, Type $type)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_type_id' => 'nullable|integer|exists:types,id',
        ]);

        $dto = TypeDTO::fromArray($data);

        return response()->json($this->service->update($type, $dto));
    }

    public function destroy(Type $type)
    {
        $this->service->delete($type);
        return response()->json(null, 204);
    }
}
