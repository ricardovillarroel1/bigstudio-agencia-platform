<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaEmitida extends Model
{
    use HasFactory;

    protected $table = 'facturas_emitidas';

    protected $fillable = [
        'user_id',
        'shopify_order_id',
        'shopify_order_number',
        'tipo_documento',
        'lioren_factura_id',
        'folio',
        'rut_receptor',
        'razon_social',
        'monto_neto',
        'monto_iva',
        'monto_total',
        'pdf_path',
        'xml_path',
        'pdf_base64', // Mantener temporalmente para compatibilidad
        'xml_base64', // Mantener temporalmente para compatibilidad
        'status',
        'error_message',
        'retry_count',
        'last_retry_at',
        'emitida_at',
        'detalles',
        'giro',
        'direccion',
        'comuna_id',
        'ciudad_id',
        'receptor_email',
    ];

    protected $casts = [
        'emitida_at' => 'datetime',
        'detalles' => 'array',
    ];

    /**
     * Guardar PDF desde base64 a archivo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savePdfFromBase64($base64Pdf)
    {
        if (!$base64Pdf) {
            return null;
        }

        $pdf = base64_decode($base64Pdf);
        $year = date('Y');
        $month = date('m');
        $directory = "facturas/{$year}/{$month}";
        
        \Storage::makeDirectory($directory);
        
        $filename = "factura_{$this->folio}_{$this->id}.pdf";
        $path = "{$directory}/{$filename}";
        
        \Storage::put($path, $pdf);
        
        return $path;
    }

    /**
     * Guardar XML desde base64 a archivo
     */
    public function saveXmlFromBase64($base64Xml)
    {
        if (!$base64Xml) {
            return null;
        }

        $xml = base64_decode($base64Xml);
        $year = date('Y');
        $month = date('m');
        $directory = "facturas/{$year}/{$month}";
        
        \Storage::makeDirectory($directory);
        
        $filename = "factura_{$this->folio}_{$this->id}.xml";
        $path = "{$directory}/{$filename}";
        
        \Storage::put($path, $xml);
        
        return $path;
    }
}