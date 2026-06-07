<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaSuscripcionItem extends Model
{
    use HasFactory;
    protected $table = 'agencia_suscripcion_items';
    protected $fillable = [
        'agencia_suscripcion_id', 'agencia_servicio_id', 'descripcion', 'monto_neto',
    ];
    protected $casts = [
        'monto_neto' => 'integer',
    ];
    public function suscripcion()
    {
        return $this->belongsTo(AgenciaSuscripcion::class, 'agencia_suscripcion_id');
    }
    public function servicio()
    {
        return $this->belongsTo(AgenciaServicio::class, 'agencia_servicio_id');
    }
}
