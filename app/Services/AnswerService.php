<?php

namespace App\Services;

use App\Models\Answer;
use App\DTOs\Answers\AnswerDTO;

class AnswerService
{
    public function index()
    {
        return Answer::all();
    }

    public function store(AnswerDTO $dto)
    {
        return Answer::create([
            'pqrs_id' => $dto->pqrs_id,
            'user_id' => $dto->user_id,
            'description' => $dto->description,
        ]);
    }

    public function update(Answer $answer, AnswerDTO $dto)
    {
        $answer->update([
            'pqrs_id' => $dto->pqrs_id,
            'user_id' => $dto->user_id,
            'description' => $dto->description,
        ]);

        return $answer;
    }

    public function destroy(Answer $answer)
    {
        $answer->delete();
    }
}
