<?php

namespace App\Http\Controllers\API;

use App\Models\City;
use Illuminate\Http\Request;
use App\Services\CityService;
use App\DTOs\Citys\CityDTO;
use App\Http\Controllers\Controller;

class CityController extends Controller
{
    public function __construct(private CityService $cityService) {}

    /**
     * @OA\Get(
     *     path="/api/cities",
     *     summary="Get all cities",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of cities",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Medellín"),
     *                 @OA\Property(property="state_id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="MED")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        return response()->json($this->cityService->getAll());
    }

    /**
     * @OA\Post(
     *     path="/api/cities",
     *     summary="Create a new city",
     *     tags={"Cities"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","state_id","code"},
     *             @OA\Property(property="name", type="string", example="Bogotá"),
     *             @OA\Property(property="state_id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="BOG")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="City created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $dto = CityDTO::fromArray($request->all());
        $city = $this->cityService->store($dto);
        return response()->json($city, 201);
    }

    public function update(Request $request, City $city)
    {
        $dto = CityDTO::fromArray($request->all());
        $updated = $this->cityService->update($city, $dto);
        return response()->json($updated);
    }

    public function destroy(City $city)
    {
        $this->cityService->delete($city);
        return response()->json(['message' => 'City deleted']);
    }
}
