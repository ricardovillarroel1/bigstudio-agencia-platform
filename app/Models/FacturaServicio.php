<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FacturaServicio extends Model
{
    use HasFactory;
    protected $table = 'facturas_servicio';
    protected $fillable = [
        'user_id',
        'suscripcion_id',
        'plan_id',
        'payment_id',
        'numero_factura',
        'concepto',
        'documentos_incluidos',
        'documentos_emitidos',
        'documentos_extra',
        'precio_extra_uf',
        'monto_extra_clp',
        'monto_plan_clp',
        'monto',
        'monto_neto',
        'monto_iva',
        'moneda',
        'periodo_inicio',
        'periodo_fin',
        'estado',
        'tipo',
        'lioren_factura_id',
        'folio',
        'pdf_base64',
        'flow_token',
        'pagada_at',
        'valor_uf_usado',
        'pdf_url',
    ];
    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'monto' => 'decimal:2',
        'monto_neto' => 'decimal:0',
        'monto_iva' => 'decimal:0',
        'monto_extra_clp' => 'decimal:0',
        'monto_plan_clp' => 'decimal:0',
        'precio_extra_uf' => 'decimal:4',
        'valor_uf_usado' => 'decimal:2',
        'pagada_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class);
    }
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
    public function estaPendiente()
    {
        return $this->estado === 'pendiente';
    }
    public function estaPagada()
    {
        return $this->estado === 'pagada';
    }
}
