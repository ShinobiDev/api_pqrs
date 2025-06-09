<?php

namespace App\Services;

use App\Models\Answer;
use App\DTOs\Answers\AnswerDTO;
use App\DTOs\Answers\EditAnswerDTO;

class AnswerService
{
    public function index()
    {
        return Answer::orderBy('id','asc')->get();
    }

    public function store(AnswerDTO $dto)
    {
        try {
            $answer = Answer::create([
                'pqrs_id' => $dto->pqrs_id,
                'user_id' => $dto->user_id,
                'description' => $dto->description,
            ]);
            return $answer;
        } catch (\Exception $e) {
            throw new \Exception('Error al crear la respuesta: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(EditAnswerDTO $dto)
    {
        try {
            $answer = Answer::findOrFail($dto->id);
            $answer->update([
                'pqrs_id' => $dto->pqrs_id,
                'user_id' => $dto->user_id,
                'description' => $dto->description,
            ]);
            return $answer;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Respuesta con ID ' . $dto->id . ' no encontrada.');
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar la respuesta: ' . $e->getMessage(), 0, $e);
        }
    }

    public function destroy(Answer $answer): bool
    {
        try {
            // Asegúrate de que el modelo Answer use el trait SoftDeletes.
            return $answer->delete();
        } catch (\Exception $e) {
            throw new \Exception('Error al eliminar la respuesta: ' . $e->getMessage(), 0, $e);
        }
    }
}
