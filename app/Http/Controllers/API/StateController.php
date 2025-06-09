<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use App\Services\StateService;
use App\DTOs\States\StateDTO;

class StateController extends Controller
{
    public function __construct(private StateService $stateService) {}

    public function index()
    {
        return response()->json($this->stateService->getAll());
    }

    public function store(Request $request)
    {
        $dto = StateDTO::fromArray($request->all());
        $state = $this->stateService->store($dto);
        return response()->json($state, 201);
    }

    public function update(Request $request, State $state)
    {
        $dto = StateDTO::fromArray($request->all());
        $updated = $this->stateService->update($state, $dto);
        return response()->json($updated);
    }

    public function destroy(State $state)
    {
        $this->stateService->delete($state);
        return response()->json(['message' => 'State deleted']);
    }
}
