<?php

namespace App\DTOs\Answers;

class EditAnswerDTO
{
    public $id;
    public $pqrs_id;
    public $user_id;
    public $description;

    public function __construct(string $id, $pqrs_id, $user_id, $description)
    {
        $this->id = $id;
        $this->pqrs_id = $pqrs_id;
        $this->user_id = $user_id;
        $this->description = $description;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['pqrs_id'],
            $data['user_id'],
            $data['description']
        );
    }
}
