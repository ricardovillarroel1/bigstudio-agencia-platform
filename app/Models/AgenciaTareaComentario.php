<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaTareaComentario extends Model
{
    protected $table = 'agencia_tarea_comentarios';

    protected $fillable = [
        'agencia_tarea_id', 'autor_user_id', 'autor_email', 'rol', 'cuerpo', 'enlace_externo',
    ];

    public const ROL_LABEL = [
        'admin'       => 'Big Studio',
        'colaborador' => 'Diseñador',
        'cliente'     => 'Cliente',
    ];

    public function tarea()
    {
        return $this->belongsTo(AgenciaTarea::class, 'agencia_tarea_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_user_id');
    }

    public function getAutorNombreAttribute()
    {
        return $this->autor->name ?? $this->autor_email ?? (self::ROL_LABEL[$this->rol] ?? 'Usuario');
    }

    public function getRolLabelAttribute()
    {
        return self::ROL_LABEL[$this->rol] ?? ucfirst($this->rol);
    }
}
