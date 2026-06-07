<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaCobro extends Model
{
    use HasFactory;

    protected $table = 'agencia_cobros';

    protected $fillable = [
        'agencia_cliente_id', 'agencia_suscripcion_id', 'concepto', 'cuota_numero', 'cuota_total', 'grupo_cuotas', 'monto',
        'estado', 'metodo_pago', 'flow_token', 'comprobante_path',
        'comprobante_original_name', 'notas_admin', 'pagado_at', 'vence_at',
        'lioren_folio', 'lioren_tipo_doc', 'lioren_pdf_url', 'lioren_xml_url',
        'factura_estado', 'factura_error',
    ];

    protected $casts = [
        'monto' => 'integer',
        'pagado_at' => 'datetime',
        'vence_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }

    public function suscripcion()
    {
        return $this->belongsTo(AgenciaSuscripcion::class, 'agencia_suscripcion_id');
    }

    public function estaPendiente()
    {
        return $this->estado === 'pendiente';
    }

    public function estaPagado()
    {
        return $this->estado === 'pagado';
    }
}
