<?php

namespace App\DTOs\Pqrs;

class PqrsDTO
{
    public $guia;
    public $name;
    public $document;
    public $phone;
    public $address;
    public $cel_phone;
    public $destiny_city_id;
    public $pqrs_type_id;
    public $description;
    public $user_id;
    public $status_id;

    public function __construct($guia, $name, $document, $phone, $address, $cel_phone, $destiny_city_id, $pqrs_type_id, $description, $user_id, $status_id)
    {
        $this->guia = $guia;
        $this->name = $name;
        $this->document = $document;
        $this->phone = $phone;
        $this->address = $address;
        $this->cel_phone = $cel_phone;
        $this->destiny_city_id = $destiny_city_id;
        $this->pqrs_type_id = $pqrs_type_id;
        $this->description = $description;
        $this->user_id = $user_id;
        $this->status_id = $status_id;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['guia'],
            $data['name'],
            $data['document'],
            $data['phone'],
            $data['address'],
            $data['cel_phone'],
            $data['destiny_city_id'],
            $data['pqrs_type_id'],
            $data['description'],
            $data['user_id'],
            $data['status_id']
        );
    }
}
