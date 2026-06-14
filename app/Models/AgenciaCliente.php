<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaCliente extends Model
{
    use HasFactory;

    protected $table = 'agencia_clientes';

    protected $fillable = [
        'nombre', 'proyecto', 'email', 'telefono', 'rut', 'razon_social',
        'giro', 'direccion_fiscal', 'ciudad', 'region', 'comuna',
        'notas', 'estado',
    ];

    /** "Andres (BOTAS MILITARES)" — nombre del cliente con su proyecto/tienda. */
    public function getNombreProyectoAttribute()
    {
        return $this->proyecto ? $this->nombre . ' (' . $this->proyecto . ')' : $this->nombre;
    }

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

    public function tareas()
    {
        return $this->hasMany(AgenciaTarea::class, 'agencia_cliente_id');
    }

    public function tareasPendientes()
    {
        return $this->tareas()->whereIn('estado', ['pendiente', 'en_curso']);
    }

    public function getNombreFacturacionAttribute()
    {
        return $this->razon_social ?: $this->nombre;
    }
}
