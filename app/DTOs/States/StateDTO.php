<?php

namespace App\DTOs\States;

class StateDTO
{
    public function __construct(
        public string $name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name']
        );
    }
}
