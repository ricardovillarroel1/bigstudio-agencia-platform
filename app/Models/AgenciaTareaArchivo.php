<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaTareaArchivo extends Model
{
    protected $table = 'agencia_tarea_archivos';

    protected $fillable = [
        'agencia_tarea_id', 'agencia_tarea_comentario_id', 'tipo',
        'subido_por_user_id', 'nombre_original', 'ruta', 'mime_type', 'tamano_bytes',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
    ];

    public function tarea()
    {
        return $this->belongsTo(AgenciaTarea::class, 'agencia_tarea_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'subido_por_user_id');
    }

    public function tamanoLegible(): string
    {
        $b = $this->tamano_bytes;
        if ($b >= 1073741824) return round($b / 1073741824, 2) . ' GB';
        if ($b >= 1048576)    return round($b / 1048576, 2) . ' MB';
        if ($b >= 1024)       return round($b / 1024, 1) . ' KB';
        return $b . ' B';
    }
}
