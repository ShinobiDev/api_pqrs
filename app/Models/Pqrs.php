<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pqrs extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
                        'guia',
                        'name',
                        'document',
                        'phone',
                        'address',
                        'cel_phone',
                        'destiny_city_id',
                        'pqrs_type_id',
                        'description',
                        'user_id',
                        'status_id'
                    ];
}
