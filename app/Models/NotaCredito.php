<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCredito extends Model
{
    use HasFactory;

    protected $table = 'notas_credito';

    protected $fillable = [
        'user_id',
        'shopify_order_id',
        'shopify_order_number',
        'tipo_documento_original',
        'folio_original',
        'lioren_nota_id',
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
        'glosa',
        'error_message',
        'emitida_at',
    ];

    protected $casts = [
        'monto_neto' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'emitida_at' => 'datetime',
    ];

    /**
     * Guardar PDF desde base64 a archivo
     */
    public function savePdfFromBase64($base64Pdf)
    {
        if (!$base64Pdf) {
            return null;
        }

        $pdf = base64_decode($base64Pdf);
        $year = date('Y');
        $month = date('m');
        $directory = "notas_credito/{$year}/{$month}";
        
        \Storage::makeDirectory($directory);
        
        $filename = "nc_{$this->folio}_{$this->id}.pdf";
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
        $directory = "notas_credito/{$year}/{$month}";
        
        \Storage::makeDirectory($directory);
        
        $filename = "nc_{$this->folio}_{$this->id}.xml";
        $path = "{$directory}/{$filename}";
        
        \Storage::put($path, $xml);
        
        return $path;
    }
}
