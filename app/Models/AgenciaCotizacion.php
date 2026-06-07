<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaCotizacion extends Model
{
    protected $table = 'agencia_cotizaciones';

    protected $fillable = [
        'numero', 'agencia_cliente_id', 'cliente_nombre', 'cliente_rut',
        'cliente_email', 'cliente_telefono', 'cliente_direccion', 'cliente_giro',
        'subtotal_neto', 'descuento_porcentaje', 'descuento_monto',
        'total_neto', 'iva', 'total', 'notas', 'estado', 'valida_hasta',
        'flow_token', 'flow_order', 'factura_estado',
        'lioren_dte_id', 'lioren_folio', 'lioren_pdf_url', 'lioren_xml_url',
        'pagado_at', 'facturado_at', 'enviada_at',
    ];

    protected $casts = [
        'valida_hasta' => 'date',
        'pagado_at' => 'datetime',
        'facturado_at' => 'datetime',
        'enviada_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }

    public function items()
    {
        return $this->hasMany(AgenciaCotizacionItem::class, 'agencia_cotizacion_id');
    }

    public function getNumeroFormateadoAttribute()
    {
        return '#' . $this->numero;
    }

    public function estaVigente()
    {
        return $this->valida_hasta && $this->valida_hasta->isFuture();
    }
}
