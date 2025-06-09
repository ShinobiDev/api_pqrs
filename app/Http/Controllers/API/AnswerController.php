<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use Illuminate\Http\Request;
use App\Services\AnswerService;
use App\DTOs\Answers\AnswerDTO;
use App\DTOs\Answers\EditAnswerDTO;

class AnswerController extends Controller
{
    public function __construct(AnswerService $service) 
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $answers = $this->service->index();
            return response()->json($answers, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al obtener las respuestas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            
            $validated = $request->validate([
                'pqrs_id' => 'required|integer|exists:pqrs,id', 
                'user_id' => 'required|integer|exists:users,id', 
                'description' => 'required|string',
            ]);

            $dto = new AnswerDTO(
                $validated['pqrs_id'],
                $validated['user_id'],
                $validated['description']
            );

            $answer = $this->service->store($dto);

            return response()->json([
                'message' => 'Respuesta creada exitosamente.',
                'answer' => $answer->toArray()
            ], 201); // 201 Created

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos para la creación de la respuesta.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar crear la respuesta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Answer $answer)
    {
        $answerId = $answer->id;
        try {
            $validated = $request->validate([
                'pqrs_id' => 'required|integer|exists:pqrs,id',
                'user_id' => 'required|integer|exists:users,id',
                'description' => 'required|string',
            ]);

            $dto = new EditAnswerDTO(
                $answerId,
                $validated['pqrs_id'],
                $validated['user_id'],
                $validated['description']
            );

            $updatedAnswer = $this->service->update($dto);

            return response()->json([
                'message' => 'Respuesta actualizada exitosamente.',
                'answer' => $updatedAnswer->toArray()
            ], 200); 

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Respuesta no encontrada.'
            ], 404); 
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos para la actualización de la respuesta.',
                'errors' => $e->errors()
            ], 422); 
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar actualizar la respuesta.',
                'error' => $e->getMessage()
            ], 500); 
        }
    }

    public function destroy(Answer $answer)
    {
        try {
            if ($answer->trashed()) {
                return response()->json([
                    'message' => 'La respuesta ya ha sido eliminada previamente.'
                ], 409); 
            }

            $deleted = $this->service->destroy($answer);

            if ($deleted) {
                return response()->json([
                    'message' => 'Respuesta eliminada exitosamente (soft deleted).'
                ], 200); // 200 OK
            } else {
                return response()->json([
                    'message' => 'No se pudo eliminar la respuesta.'
                ], 500);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Respuesta no encontrada.'
            ], 404); 
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar eliminar la respuesta.',
                'error' => $e->getMessage()
            ], 500); 
        }
    }
}
