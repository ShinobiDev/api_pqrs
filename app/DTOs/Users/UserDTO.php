<?php

namespace App\DTOs\Users;

class UserDTO
{
    public $name;
    public $document_type_id;
    public $document;
    public $role_id;
    public $email;
    public $phone;
    public $status_id;
    public $password;

    public function __construct($name, $document_type_id, $document, $role_id, $email, $phone, $status_id, $password)
    {
        $this->name = $name;
        $this->document_type_id = $document_type_id;
        $this->document = $document;
        $this->role_id = $role_id;
        $this->email = $email;
        $this->phone = $phone;
        $this->status_id = $status_id;
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            $data['document_type_id'],
            $data['document'],
            $data['role_id'],
            $data['email'],
            $data['phone'],
            $data['status_id'],
            $data['password']
        );
    }
}
