<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pqrs extends Model
{
    protected $fillable = [
        'tipo',
        'nombre',
        'correo',
        'descripcion',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
