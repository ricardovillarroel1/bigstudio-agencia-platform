<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaServicio extends Model
{
    use HasFactory;

    protected $table = 'agencia_servicios';

    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'moneda', 'precio_uf', 'periodicidad', 'activo',
    ];

    protected $casts = [
        'precio' => 'integer',
        'precio_uf' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public function clienteServicios()
    {
        return $this->hasMany(AgenciaClienteServicio::class, 'agencia_servicio_id');
    }
}
