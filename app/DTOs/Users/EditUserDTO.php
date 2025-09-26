<?php

namespace App\DTOs\Users;

class EditUserDTO
{
    public $id;
    public $name;
    public $user_type_id;
    public $document_type_id;
    public $document;
    public $role_id;
    public $email;
    public $phone;
    public $status_id;
    public $password;
    public $client_id;

    public function __construct($id, $name, $user_type_id, $document_type_id, $document, $role_id, $email, $phone, $status_id, $password = null, $client_id = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->user_type_id = $user_type_id;
        $this->document_type_id = $document_type_id;
        $this->document = $document;
        $this->role_id = $role_id;
        $this->email = $email;
        $this->phone = $phone;
        $this->status_id = $status_id;
        $this->password = $password;
        $this->client_id = $client_id;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['user_type_id'],
            $data['document_type_id'],
            $data['document'],
            $data['role_id'],
            $data['email'],
            $data['phone'],
            $data['status_id'],
            $data['password'] ?? null,
            $data['client_id'] ?? null
        );
    }
}
