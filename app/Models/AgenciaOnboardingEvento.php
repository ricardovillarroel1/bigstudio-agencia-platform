<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgenciaOnboardingEvento extends Model
{
    protected $table = "agencia_onboarding_eventos";
    public $timestamps = false;

    protected $fillable = [
        "proyecto_id",
        "tipo",
        "descripcion",
        "metadata",
        "ip",
        "user_agent",
        "created_at",
    ];

    protected $casts = [
        "metadata" => "array",
        "created_at" => "datetime",
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingProyecto::class, "proyecto_id");
    }

    public static function registrar(int $proyectoId, string $tipo, ?string $descripcion = null, array $metadata = []): self
    {
        $request = request();
        return self::create([
            "proyecto_id" => $proyectoId,
            "tipo" => $tipo,
            "descripcion" => $descripcion,
            "metadata" => $metadata ?: null,
            "ip" => $request?->ip(),
            "user_agent" => $request?->userAgent(),
            "created_at" => now(),
        ]);
    }
}
