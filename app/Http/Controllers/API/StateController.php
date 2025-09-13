<?php

namespace App\Http\Controllers\API;

use App\Models\State;
use Illuminate\Http\Request;
use App\Services\StateService;
use App\DTOs\States\StateDTO;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function __construct(private StateService $stateService) {}

    /**
     * @OA\Get(
     *     path="/api/states",
     *     summary="Get all states",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of states",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Antioquia"),
     *                 @OA\Property(property="code", type="string", example="ANT")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json($this->stateService->getAll());
    }

    /**
     * @OA\Post(
     *     path="/api/states",
     *     summary="Create a new state",
     *     tags={"States"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string", example="Cundinamarca"),
     *             @OA\Property(property="code", type="string", example="CUN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="State created successfully"
     *     )
     * )
     */
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
