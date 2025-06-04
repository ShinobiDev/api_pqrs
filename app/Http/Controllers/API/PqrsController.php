<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pqrs;
use Illuminate\Http\Request;
use App\Services\PqrsService;
use App\DTOs\Pqrs\PqrsDTO;

class PqrsController extends Controller
{
    public function __construct(PqrsService $service) {}

    public function index()
    {
        return response()->json($this->service->index());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guia' => 'required|string',
            'name' => 'required|string',
            'identification' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'cel_phon' => 'required|string',
            'destination_city' => 'required|string',
            'pqrs_type_id' => 'required|integer',
            'description' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        $dto = PqrsDTO::fromArray($validated);
        $pqrs = $this->service->store($dto);

        return response()->json($pqrs, 201);
    }

    public function update(Request $request, Pqrs $pqrs)
    {
        $validated = $request->validate([
            'guia' => 'required|string',
            'name' => 'required|string',
            'identification' => 'required|string',
            'phone' => 'required|string',
            'address' => 'required|string',
            'cel_phon' => 'required|string',
            'destination_city' => 'required|string',
            'pqrs_type_id' => 'required|integer',
            'description' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        $dto = PqrsDTO::fromArray($validated);
        $updated = $this->service->update($pqrs, $dto);

        return response()->json($updated);
    }

    public function destroy(Pqrs $pqrs)
    {
        $this->service->destroy($pqrs);
        return response()->json(['message' => 'PQRS deleted']);
    }
}
