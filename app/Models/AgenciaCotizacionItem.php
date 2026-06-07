<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaCotizacionItem extends Model
{
    protected $table = 'agencia_cotizacion_items';

    protected $fillable = [
        'agencia_cotizacion_id', 'agencia_servicio_id', 'codigo',
        'descripcion', 'cantidad', 'precio_unitario_neto', 'total_neto',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(AgenciaCotizacion::class, 'agencia_cotizacion_id');
    }

    public function servicio()
    {
        return $this->belongsTo(AgenciaServicio::class, 'agencia_servicio_id');
    }
}
