<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoTransferencia extends Model
{
    use HasFactory;

    protected $table = 'pago_transferencias';

    protected $fillable = [
        'user_id',
        'plan_id',
        'periodo',
        'monto',
        'comprobante_path',
        'comprobante_original_name',
        'status',
        'notas_admin',
        'revisado_por',
        'revisado_at',
    ];

    protected $casts = [
        'revisado_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
