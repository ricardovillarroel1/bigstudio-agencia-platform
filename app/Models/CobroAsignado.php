<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CobroAsignado extends Model
{
    use HasFactory;

    protected $table = 'cobros_asignados';

    protected $fillable = [
        'admin_id',
        'cliente_id',
        'concepto',
        'monto',
        'estado',
        'flow_token',
        'payment_id',
        'factura_servicio_id',
        'pagado_at',
    ];

    protected $casts = [
        'pagado_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function facturaServicio()
    {
        return $this->belongsTo(FacturaServicio::class);
    }
}
