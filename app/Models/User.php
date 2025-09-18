<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'document_type_id',
        'document',
        'role_id',
        'email',
        'phone',
        'status_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relación con el tipo de usuario (types)
    public function type()
    {
        return $this->belongsTo(Type::class, 'user_type_id');
    }

    // Relación con el cliente (usuarios relacionados)
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Relación con el tipo de documento
    public function documentType()
    {
        return $this->belongsTo(Type::class, 'document_type_id');
    }

    // Relación con el rol
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relación con el estado
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
