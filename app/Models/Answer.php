<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Answer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pqrs_id',
        'user_id',
        'description',
    ];

    public function pqrs()
    {
        return $this->belongsTo(Pqrs::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
