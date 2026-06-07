<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgenciaOnboardingPlantilla extends Model
{
    protected $table = "agencia_onboarding_plantillas";

    protected $fillable = [
        "nombre",
        "slug",
        "tipo_servicio",
        "descripcion",
        "dias_habiles_estimados",
        "secciones",
        "activo",
    ];

    protected $casts = [
        "secciones" => "array",
        "activo" => "boolean",
        "dias_habiles_estimados" => "integer",
    ];

    public function proyectos(): HasMany
    {
        return $this->hasMany(AgenciaOnboardingProyecto::class, "plantilla_id");
    }

    public function scopeActivas($query)
    {
        return $query->where("activo", true);
    }
}
