<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaCorreo extends Model
{
    protected $table = 'agencia_correos';

    protected $fillable = [
        'agencia_cliente_id',
        'destinatario_email',
        'destinatario_nombre',
        'asunto',
        'vista_previa',
        'contenido',
        'adjuntos',
        'estado',
        'enviado_at',
        'error_mensaje',
    ];

    protected $casts = [
        'adjuntos' => 'array',
        'enviado_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }
}
