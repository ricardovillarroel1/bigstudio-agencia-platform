<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaSuscripcion extends Model
{
    use HasFactory;

    protected $table = 'agencia_suscripciones';

    protected $fillable = [
        'agencia_cliente_id', 'agencia_cliente_servicio_id', 'concepto', 'descripcion',
        'monto', 'periodicidad', 'estado', 'fecha_inicio', 'proximo_cobro',
        'fecha_fin', 'facturacion_automatica', 'dias_anticipacion_factura',
        'reminder_sent', 'reminder_sent_day', 'factura_ciclo_emitida',
    ];

    protected $casts = [
        'monto' => 'integer',
        'fecha_inicio' => 'date',
        'proximo_cobro' => 'date',
        'fecha_fin' => 'date',
        'facturacion_automatica' => 'boolean',
        'reminder_sent' => 'boolean',
        'reminder_sent_day' => 'boolean',
        'factura_ciclo_emitida' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }

    public function clienteServicio()
    {
        return $this->belongsTo(AgenciaClienteServicio::class, 'agencia_cliente_servicio_id');
    }

    public function items()
    {
        return $this->hasMany(AgenciaSuscripcionItem::class, "agencia_suscripcion_id");
    }

    public function cobros()
    {
        return $this->hasMany(AgenciaCobro::class, 'agencia_suscripcion_id');
    }

    public function estaActiva()
    {
        return $this->estado === 'activa';
    }

    public function diasRestantes()
    {
        return now()->diffInDays($this->proximo_cobro, false);
    }

    public function resetReminders()
    {
        $this->update([
            'reminder_sent' => false,
            'reminder_sent_day' => false,
            'factura_ciclo_emitida' => false,
        ]);
    }
}
