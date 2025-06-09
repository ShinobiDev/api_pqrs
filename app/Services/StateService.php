<?php

namespace App\Services;

use App\Models\State;
use App\DTOs\States\StateDTO;

class StateService
{
    public function getAll()
    {
        return State::all();
    }

    public function store(StateDTO $dto): State
    {
        return State::create([
            'name' => $dto->name,
        ]);
    }

    public function update(State $state, StateDTO $dto): State
    {
        $state->update([
            'name' => $dto->name,
        ]);

        return $state;
    }

    public function delete(State $state): void
    {
        $state->delete();
    }
}
