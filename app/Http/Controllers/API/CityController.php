<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use App\Services\CityService;
use App\DTOs\Cities\CityDTO;

class CityController extends Controller
{
    public function __construct(private CityService $cityService) {}

    public function index()
    {
        return response()->json($this->cityService->getAll());
    }

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
