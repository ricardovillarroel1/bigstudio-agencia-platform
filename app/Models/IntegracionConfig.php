<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegracionConfig extends Model
{
    use HasFactory;

    protected $table = 'integracion_configs';

    protected $fillable = [
        'user_id',
        'solicitud_id',
        'shopify_tienda',
        'shopify_token',
        'shopify_secret',
        'lioren_api_key',
        'facturacion_enabled',
        'shopify_visibility_enabled',
        'notas_credito_enabled',
        'order_limit_enabled',
        'sync_inventario_enabled',
        'default_bodega_id',
        'monthly_order_limit',
        'activo',
        'ultima_sincronizacion',
        'auth_method',        // NUEVO - OAuth 2.0
        'oauth_installed_at', // NUEVO - OAuth 2.0
        'shop_domain',        // NUEVO - OAuth 2.0
        'shopify_client_id',  // Credenciales por cliente
        'shopify_client_secret', // Credenciales por cliente
    ];

    protected $casts = [
        'facturacion_enabled' => 'boolean',
        'shopify_visibility_enabled' => 'boolean',
        'notas_credito_enabled' => 'boolean',
        'order_limit_enabled' => 'boolean',
        'sync_inventario_enabled' => 'boolean',
        'default_bodega_id' => 'integer',
        'activo' => 'boolean',
        'ultima_sincronizacion' => 'datetime',
        'oauth_installed_at' => 'datetime',
    ];

    // Accessors y Mutators para encriptación segura que manejan valores vacíos
    public function getShopifyTokenAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            \Log::error('Error desencriptando shopify_token: ' . $e->getMessage());
            return null;
        }
    }

    public function setShopifyTokenAttribute($value)
    {
        $this->attributes['shopify_token'] = $value ? encrypt($value) : null;
    }

    public function getShopifySecretAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            \Log::error('Error desencriptando shopify_secret: ' . $e->getMessage());
            return null;
        }
    }

    public function setShopifySecretAttribute($value)
    {
        $this->attributes['shopify_secret'] = $value ? encrypt($value) : null;
    }

    public function getLiorenApiKeyAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return decrypt($value);
        } catch (\Exception $e) {
            \Log::error('Error desencriptando lioren_api_key: ' . $e->getMessage());
            return null;
        }
    }

    public function setLiorenApiKeyAttribute($value)
    {
        $this->attributes['lioren_api_key'] = $value ? encrypt($value) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function webhooks()
    {
        return $this->hasMany(ClienteWebhook::class, 'user_id', 'user_id');
    }

    /**
     * Obtener la configuración activa (método legacy para compatibilidad)
     */
    public static function getActiva()
    {
        return self::where('activo', true)->first();
    }

    /**
     * Obtener la configuración activa de un usuario específico
     */
    public static function getActivaByUser($userId)
    {
        return self::where('user_id', $userId)
            ->where('activo', true)
            ->first();
    }
}
