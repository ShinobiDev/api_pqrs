<?php

namespace App\DTOs;

class AnswerDTO
{
    public $pqrs_id;
    public $user_id;
    public $description;

    public function __construct($pqrs_id, $user_id, $description)
    {
        $this->pqrs_id = $pqrs_id;
        $this->user_id = $user_id;
        $this->description = $description;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['pqrs_id'],
            $data['user_id'],
            $data['description']
        );
    }
}
