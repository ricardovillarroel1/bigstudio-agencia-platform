<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgenciaContratoPlantilla extends Model
{
    protected $table = "agencia_contrato_plantillas";

    protected $fillable = [
        "nombre", "slug", "tipo_servicio", "intro", "clausulas", "cierre", "activo",
    ];

    protected $casts = [
        "clausulas" => "array",
        "activo" => "boolean",
    ];

    public function scopeActivas($q) { return $q->where("activo", true); }
}
