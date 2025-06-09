<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\State;

class CitySeeder extends Seeder
{
    public function run()
    {
        $state = State::where('name', 'Antioquia')->first();

        if ($state) {
            City::create([
                'name' => 'Medellín',
                'state_id' => $state->id,
                'dane_code' => '05001'
            ]);

            City::create([
                'name' => 'Envigado',
                'state_id' => $state->id,
                'dane_code' => '05266'
            ]);
        }

        $state = State::where('name', 'Distrito Capital')->first();

        if ($state) {
            City::create([
                'name' => 'Bogotá',
                'state_id' => $state->id,
                'dane_code' => '11001'
            ]);
        }
    }
}
