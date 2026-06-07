<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgenciaOnboardingComentario extends Model
{
    protected $table = "agencia_onboarding_comentarios";

    protected $fillable = [
        "proyecto_id",
        "seccion_key",
        "mensaje",
        "autor",
        "resuelto",
    ];

    protected $casts = [
        "resuelto" => "boolean",
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingProyecto::class, "proyecto_id");
    }
}
