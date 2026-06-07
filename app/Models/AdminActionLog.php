<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdminActionLog extends Model
{
    protected $table = 'admin_action_logs';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'admin_email',
        'action',
        'target_type',
        'target_id',
        'target_user_id',
        'metadata',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Helper para registrar una acción del admin actual.
     *
     * Ejemplos:
     *   AdminActionLog::record('marcar_pagada', $factura, ['monto' => $factura->monto], $factura->user_id);
     *   AdminActionLog::record('pausar', $suscripcion, ['pausada_antes' => false], $suscripcion->user_id);
     */
    public static function record(string $action, $target = null, array $metadata = [], $targetUserId = null): self
    {
        $admin = Auth::user();

        $targetType = null;
        $targetId   = null;
        if ($target) {
            $class = is_object($target) ? get_class($target) : null;
            $targetType = match ($class) {
                \App\Models\FacturaServicio::class => 'factura_servicio',
                \App\Models\Suscripcion::class    => 'suscripcion',
                default                            => $class ? strtolower(class_basename($class)) : null,
            };
            $targetId = $target->id ?? null;
        }

        return self::create([
            'admin_id'       => $admin->id ?? null,
            'admin_email'    => $admin->email ?? null,
            'action'         => $action,
            'target_type'    => $targetType,
            'target_id'      => $targetId,
            'target_user_id' => $targetUserId,
            'metadata'       => $metadata,
            'ip_address'     => Request::ip(),
            'created_at'     => now(),
        ]);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Etiqueta legible para el tipo de acción.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'marcar_pagada'    => 'Marcó factura como pagada',
            'emitir_dte'       => 'Emitió DTE',
            'pausar'           => 'Pausó suscripción',
            'reanudar'         => 'Reanudó suscripción',
            'reiniciar_ciclo'  => 'Reinició ciclo',
            default            => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'marcar_pagada'   => '✅',
            'emitir_dte'      => '⚡',
            'pausar'          => '⏸️',
            'reanudar'        => '▶️',
            'reiniciar_ciclo' => '🔄',
            default           => '•',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'marcar_pagada'   => '#059669',
            'emitir_dte'      => '#10B981',
            'pausar'          => '#F59E0B',
            'reanudar'        => '#059669',
            'reiniciar_ciclo' => '#DC2626',
            default           => '#6B7280',
        };
    }
}
