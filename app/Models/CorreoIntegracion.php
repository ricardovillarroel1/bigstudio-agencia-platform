<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorreoIntegracion extends Model
{
    protected $table = 'correos_integracion';

    protected $fillable = [
        'user_id',
        'destinatario_nombre',
        'destinatario_email',
        'asunto',
        'contenido',
        'estado',
        'tipo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
