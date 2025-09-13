<?php

namespace App\Services;

use App\Models\City;
use App\DTOs\Citys\CityDTO;

class CityService
{
    public function getAll()
    {
        return City::with('state')->get();
    }

    public function store(CityDTO $dto): City
    {
        return City::create([
            'name' => $dto->name,
            'state_id' => $dto->state_id,
            'dane_code' => $dto->dane_code,
        ]);
    }

    public function update(City $city, CityDTO $dto): City
    {
        $city->update([
            'name' => $dto->name,
            'state_id' => $dto->state_id,
            'dane_code' => $dto->dane_code,
        ]);

        return $city;
    }

    public function delete(City $city): void
    {
        $city->delete();
    }
}
