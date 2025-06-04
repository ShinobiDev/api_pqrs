<?php

namespace App\DTOs\Users;

class EditUserDTO
{
    public $id;
    public $name;
    public $document_type_id;
    public $document;
    public $role_id;
    public $email;
    public $phone;
    public $status_id;

    public function __construct($id, $name, $document_type_id, $document, $role_id, $email, $phone, $status_id)
    {
        $this->id = $id;
        $this->name = $name;
        $this->document_type_id = $document_type_id;
        $this->document = $document;
        $this->role_id = $role_id;
        $this->email = $email;
        $this->phone = $phone;
        $this->status_id = $status_id;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['document_type_id'],
            $data['document'],
            $data['role_id'],
            $data['email'],
            $data['phone'],
            $data['status_id']
        );
    }
}
