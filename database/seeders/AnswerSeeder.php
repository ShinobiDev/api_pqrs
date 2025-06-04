<?php

namespace Database\Seeders;

use App\Models\Answer;
use Illuminate\Database\Seeder;

class AnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Answer::create([
            'pqrs_id' => 1,
            'user_id' => 1,
            'description' => 'Gracias por contactarnos. Estamos revisando su caso.',
        ]);
    }
}
