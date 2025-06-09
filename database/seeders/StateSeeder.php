<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;

class StateSeeder extends Seeder
{
    public function run()
    {
        $states = [
            'Amazonas',
            'Antioquia',
            'Arauca',
            'Atlántico',
            'Bolívar',
            'Boyacá',
            'Caldas',
            'Caquetá',
            'Casanare',
            'Cauca',
            'Cesar',
            'Chocó',
            'Córdoba',
            'Cundinamarca',
            'Guainía',
            'Guajira',
            'Huila',
            'La Guajira',
            'Magdalena',
            'Meta',
            'Norte de Santander',
            'Nariño',
            'Putumayo',
            'Quindío',
            'Risaralda',
            'Santander',
            'Sucre',
            'Tolima',
            'Valle del Cauca',
            'Vaupés',
            'Vichada',
            'Distrito Capital'
        ];

        foreach ($states as $state) {
            State::create(['name' => $state]);
        }
    }
}
