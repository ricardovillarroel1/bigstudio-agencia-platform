<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaCliente extends Model
{
    use HasFactory;

    protected $table = 'agencia_clientes';

    protected $fillable = [
        'nombre', 'email', 'telefono', 'rut', 'razon_social',
        'giro', 'direccion_fiscal', 'ciudad', 'region', 'comuna',
        'notas', 'estado',
    ];

    public function servicios()
    {
        return $this->hasMany(AgenciaClienteServicio::class, 'agencia_cliente_id');
    }

    public function suscripciones()
    {
        return $this->hasMany(AgenciaSuscripcion::class, 'agencia_cliente_id');
    }

    public function cobros()
    {
        return $this->hasMany(AgenciaCobro::class, 'agencia_cliente_id');
    }

    public function suscripcionesActivas()
    {
        return $this->suscripciones()->where('estado', 'activa');
    }

    public function getNombreFacturacionAttribute()
    {
        return $this->razon_social ?: $this->nombre;
    }
}
