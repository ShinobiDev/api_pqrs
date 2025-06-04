<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use Illuminate\Http\Request;
use App\Services\AnswerService;
use App\DTOs\Answers\AnswerDTO;

class AnswerController extends Controller
{
    public function __construct(AnswerService $service) {}

    public function index()
    {
        return response()->json($this->service->index());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pqrs_id' => 'required|integer',
            'user_id' => 'required|integer',
            'description' => 'required|string',
        ]);

        $dto = AnswerDTO::fromArray($validated);
        $answer = $this->service->store($dto);

        return response()->json($answer, 201);
    }

    public function update(Request $request, Answer $answer)
    {
        $validated = $request->validate([
            'pqrs_id' => 'required|integer',
            'user_id' => 'required|integer',
            'description' => 'required|string',
        ]);

        $dto = AnswerDTO::fromArray($validated);
        $updated = $this->service->update($answer, $dto);

        return response()->json($updated);
    }

    public function destroy(Answer $answer)
    {
        $this->service->destroy($answer);
        return response()->json(['message' => 'Answer deleted']);
    }
}
