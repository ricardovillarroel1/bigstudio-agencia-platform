<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaClienteServicio extends Model
{
    use HasFactory;

    protected $table = 'agencia_cliente_servicio';

    protected $fillable = [
        'agencia_cliente_id', 'agencia_servicio_id', 'precio_acordado',
        'inversion_publicidad', 'plataforma_publicidad', 'notas_internas',
        'estado', 'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'precio_acordado' => 'integer',
        'inversion_publicidad' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }

    public function servicio()
    {
        return $this->belongsTo(AgenciaServicio::class, 'agencia_servicio_id');
    }

    public function suscripciones()
    {
        return $this->hasMany(AgenciaSuscripcion::class, 'agencia_cliente_servicio_id');
    }
}
