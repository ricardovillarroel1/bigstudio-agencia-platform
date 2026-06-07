<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boleta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shopify_order_id', // AGREGADO: Para vincular con pedidos de Shopify
        'lioren_id',
        'tipodoc',
        'folio',
        'fecha',
        'receptor_rut',
        'receptor_nombre',
        'receptor_email',
        'monto_neto',
        'monto_exento',
        'monto_iva',
        'monto_total',
        'pdf_path',
        'xml_path',
        'pdf_base64', // Mantener temporalmente para compatibilidad
        'xml_base64', // Mantener temporalmente para compatibilidad
        'detalles',
        'pagos',
        'observaciones',
        'status',
        'error_message',
        'retry_count',
        'last_retry_at',
    ];

    protected $casts = [
        'detalles' => 'array',
        'pagos' => 'array',
        'fecha' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPdfUrlAttribute()
    {
        return route('boletas.pdf', $this->id);
    }

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
        $directory = "boletas/{$year}/{$month}";
        
        // Crear directorio si no existe
        \Storage::makeDirectory($directory);
        
        $filename = "boleta_{$this->folio}_{$this->id}.pdf";
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
        $directory = "boletas/{$year}/{$month}";
        
        \Storage::makeDirectory($directory);
        
        $filename = "boleta_{$this->folio}_{$this->id}.xml";
        $path = "{$directory}/{$filename}";
        
        \Storage::put($path, $xml);
        
        return $path;
    }
}
