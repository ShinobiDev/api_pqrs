<?php

namespace App\DTOs;

class PqrsDTO
{
    public $guia;
    public $name;
    public $identification;
    public $phone;
    public $address;
    public $cel_phon;
    public $destination_city;
    public $pqrs_type_id;
    public $description;
    public $user_id;

    public function __construct($guia, $name, $identification, $phone, $address, $cel_phon, $destination_city, $pqrs_type_id, $description, $user_id)
    {
        $this->guia = $guia;
        $this->name = $name;
        $this->identification = $identification;
        $this->phone = $phone;
        $this->address = $address;
        $this->cel_phon = $cel_phon;
        $this->destination_city = $destination_city;
        $this->pqrs_type_id = $pqrs_type_id;
        $this->description = $description;
        $this->user_id = $user_id;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['guia'],
            $data['name'],
            $data['identification'],
            $data['phone'],
            $data['address'],
            $data['cel_phon'],
            $data['destination_city'],
            $data['pqrs_type_id'],
            $data['description'],
            $data['user_id']
        );
    }
}
