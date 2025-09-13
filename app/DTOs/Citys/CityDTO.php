<?php

namespace App\DTOs\Citys;

class CityDTO
{
    public function __construct(
        public string $name,
        public int $state_id,
        public ?string $dane_code = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['state_id'],
            $data['dane_code'] ?? null
        );
    }
}
