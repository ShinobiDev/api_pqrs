<?php

namespace App\Services;

use App\Models\Status;
use App\DTOs\Statuses\StatusDTO;

class StatusService
{
    public function getAll()
    {
        return Status::all();
    }

    public function create(StatusDTO $dto): Status
    {
        return Status::create(['name' => $dto->name]);
    }

    public function update(Status $status, StatusDTO $dto): Status
    {
        $status->update(['name' => $dto->name]);
        return $status;
    }

    public function delete(Status $status): void
    {
        $status->delete();
    }
}
