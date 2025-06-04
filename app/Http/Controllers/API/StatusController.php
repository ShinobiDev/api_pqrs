<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\StatusService;
use App\DTOs\Statuses\StatusDTO;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    protected $service;

    public function __construct(StatusService $service)
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

        $dto = StatusDTO::fromArray($data);

        return response()->json($this->service->create($dto), 201);
    }

    public function update(Request $request, Status $status)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $dto = StatusDTO::fromArray($data);

        return response()->json($this->service->update($status, $dto));
    }

    public function destroy(Status $status)
    {
        $this->service->delete($status);
        return response()->json(null, 204);
    }
}
