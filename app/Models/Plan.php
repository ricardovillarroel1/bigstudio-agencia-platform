<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Plan extends Model
{
    use HasFactory;
    protected $table = 'planes';
    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'caracteristicas',
        'facturacion_enabled',
        'boletas_enabled',
        'shopify_visibility_enabled',
        'notas_credito_enabled',
        'order_limit_enabled',
        'sync_inventario_enabled',
        'documentos_postventa_enabled',
        'monthly_order_limit',
        'precio',
        'precio_anual',
        'descuento_anual',
        'plan_anual_activo',
        'moneda',
        'activo',
    ];
    protected $casts = [
        'caracteristicas' => 'array',
        'facturacion_enabled' => 'boolean',
        'boletas_enabled' => 'boolean',
        'shopify_visibility_enabled' => 'boolean',
        'notas_credito_enabled' => 'boolean',
        'order_limit_enabled' => 'boolean',
        'sync_inventario_enabled' => 'boolean',
        'documentos_postventa_enabled' => 'boolean',
        'precio' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'plan_anual_activo' => 'boolean',
        'activo' => 'boolean',
    ];
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
