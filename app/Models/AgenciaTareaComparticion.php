<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaTareaComparticion extends Model
{
    protected $table = 'agencia_tarea_comparticiones';

    protected $fillable = [
        'agencia_tarea_id', 'user_id', 'email', 'compartida_en', 'primer_acceso_en', 'ultimo_visto_comentarios_en',
    ];

    protected $casts = [
        'compartida_en'               => 'datetime',
        'primer_acceso_en'            => 'datetime',
        'ultimo_visto_comentarios_en' => 'datetime',
    ];

    public function tarea()
    {
        return $this->belongsTo(AgenciaTarea::class, 'agencia_tarea_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
