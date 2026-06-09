<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgenciaTarea extends Model
{
    use HasFactory;

    protected $table = 'agencia_tareas';

    protected $fillable = [
        'agencia_cliente_id', 'titulo', 'descripcion', 'estado', 'prioridad',
        'fecha_limite', 'terminada_en', 'creado_por',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
        'terminada_en' => 'datetime',
    ];

    public const ESTADOS = ['borrador', 'pendiente', 'en_curso', 'en_revision', 'requiere_cambios', 'terminado'];

    public const ESTADOS_LABEL = [
        'borrador'         => 'Borrador',
        'pendiente'        => 'Pendiente',
        'en_curso'         => 'En curso',
        'en_revision'      => 'En revisión',
        'requiere_cambios' => 'Requiere cambios',
        'terminado'        => 'Terminado',
    ];

    /** Estados que cuentan como trabajo "por hacer" (para KPIs y badges). */
    public const ESTADOS_PENDIENTES = ['pendiente', 'en_curso', 'en_revision', 'requiere_cambios'];

    /** Orden de prioridad para listar (lo que necesita atención primero). */
    public const ORDEN_ESTADO = "FIELD(estado,'requiere_cambios','en_revision','en_curso','pendiente','borrador','terminado')";

    public function cliente()
    {
        return $this->belongsTo(AgenciaCliente::class, 'agencia_cliente_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function comparticiones()
    {
        return $this->hasMany(AgenciaTareaComparticion::class, 'agencia_tarea_id');
    }

    public function comentarios()
    {
        return $this->hasMany(AgenciaTareaComentario::class, 'agencia_tarea_id');
    }

    public function archivos()
    {
        return $this->hasMany(AgenciaTareaArchivo::class, 'agencia_tarea_id');
    }

    /** Tareas que cuentan como "por hacer". */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', self::ESTADOS_PENDIENTES);
    }

    /**
     * Solo las tareas compartidas con un usuario dado (vista del colaborador).
     * Matchea por user_id O por email, para que también vea las que se compartieron
     * a su correo antes de que su cuenta existiera (user_id quedaba nulo).
     */
    public function scopeCompartidasCon($query, $user)
    {
        return $query->whereHas('comparticiones', function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if (!empty($user->email)) {
                $q->orWhere('email', $user->email);
            }
        });
    }

    public function getEstadoLabelAttribute()
    {
        return self::ESTADOS_LABEL[$this->estado] ?? ucfirst($this->estado);
    }
}
