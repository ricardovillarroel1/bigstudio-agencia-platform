<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgenciaOnboardingRespuesta extends Model
{
    protected $table = "agencia_onboarding_respuestas";

    protected $fillable = [
        "proyecto_id",
        "seccion_key",
        "campo_key",
        "valor",
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingProyecto::class, "proyecto_id");
    }
}
