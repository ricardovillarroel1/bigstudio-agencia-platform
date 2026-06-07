<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgenciaOnboardingProducto extends Model
{
    protected $table = "agencia_onboarding_productos";

    protected $fillable = [
        "proyecto_id",
        "archivo_id",
        "seccion_key",
        "campo_key",
        "productos",
        "total_productos",
        "total_variantes",
        "warnings",
        "errores",
        "parseado_at",
    ];

    protected $casts = [
        "productos" => "array",
        "warnings" => "array",
        "errores" => "array",
        "parseado_at" => "datetime",
        "total_productos" => "integer",
        "total_variantes" => "integer",
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingProyecto::class, "proyecto_id");
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingArchivo::class, "archivo_id");
    }

    public function tieneErrores(): bool
    {
        return is_array($this->errores) && count($this->errores) > 0;
    }

    public function tieneWarnings(): bool
    {
        return is_array($this->warnings) && count($this->warnings) > 0;
    }
}
