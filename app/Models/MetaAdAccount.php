<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdAccount extends Model
{
    protected $table = 'meta_ad_accounts';

    protected $fillable = [
        'agencia_cliente_id',
        'nombre_cuenta',
        'act_id',
        'moneda',
        'estado',
        'ultima_sync_at',
        'reporte_emails',
        'reporte_dias',
        'reporte_activo',
        'reporte_token',
        'reporte_ultimo_envio',
    ];

    protected $casts = [
        'ultima_sync_at' => 'datetime',
        'reporte_ultimo_envio' => 'datetime',
        'reporte_emails' => 'array',
        'reporte_activo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }

    public function insights()
    {
        return $this->hasMany(MetaAdInsight::class, 'meta_ad_account_id');
    }
}
