<?php

namespace App\DTOs\Statuses;

class EditStatusDTO
{
    public $id;
    public $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['id'], $data['name']);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'], // O 'integer' si tu ID es numérico
            'name' => ['required', 'string', 'max:20', Rule::unique('statuses')->ignore($this->id)], // Si el nombre debe ser único, ignorando el actual
        ];
    }
}
