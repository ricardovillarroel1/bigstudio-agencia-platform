<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    protected $fillable = [
        'user_id',
        'plan_id',
        'estado',
        'pausada',
        'pausada_at',
        'motivo_pausa',
        'fecha_inicio',
        'fecha_fin',
        'proximo_pago',
        'origen',
        'reminder_7d_sent',
        'reminder_3d_sent',
        'reminder_0d_sent',
        'suspension_email_sent',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'proximo_pago' => 'date',
        'pausada' => 'boolean',
        'pausada_at' => 'datetime',
        'reminder_7d_sent' => 'boolean',
        'reminder_3d_sent' => 'boolean',
        'reminder_0d_sent' => 'boolean',
        'suspension_email_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function pagos()
    {
        return $this->hasMany(Payment::class, 'suscripcion_id');
    }

    public function facturasServicio()
    {
        return $this->hasMany(FacturaServicio::class, 'suscripcion_id');
    }

    public function estaActiva()
    {
        return $this->estado === 'activa' && !$this->pausada;
    }

    public function estaPausada()
    {
        return $this->pausada;
    }

    public function estaVencida()
    {
        return $this->estado === 'vencida';
    }

    public function diasRestantes()
    {
        return now()->diffInDays($this->proximo_pago, true); // FIX: true para excluir hoy
    }

    /**
     * Resetear los flags de recordatorio cuando se renueva la suscripción.
     * Llamar este método al renovar/pagar el plan.
     */
    public function resetReminders()
    {
        $this->update([
            'reminder_7d_sent' => false,
            'reminder_3d_sent' => false,
            'reminder_0d_sent' => false,
            'suspension_email_sent' => false,
        ]);
    }
}
