<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AgenciaOnboardingProyecto extends Model
{
    protected $table = "agencia_onboarding_proyectos";

    protected $fillable = [
        "agencia_cliente_id",
        "plantilla_id",
        "token",
        "titulo",
        "email_cliente",
        "logo_cliente_archivo_id",
        "video_bienvenida_url",
        "estado",
        "porcentaje_avance",
        "fecha_envio",
        "fecha_primer_acceso",
        "fecha_completado",
        "token_expira_en",
        "notas_internas",
    ];

    protected $casts = [
        "fecha_envio" => "datetime",
        "fecha_primer_acceso" => "datetime",
        "fecha_completado" => "datetime",
        "token_expira_en" => "datetime",
        "porcentaje_avance" => "integer",
    ];

    protected static function booted(): void
    {
        static::creating(function (self $proyecto) {
            if (empty($proyecto->token)) {
                $proyecto->token = self::generarTokenUnico();
            }
        });
    }

    public static function generarTokenUnico(): string
    {
        do {
            $token = Str::random(40);
        } while (self::where("token", $token)->exists());
        return $token;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(AgenciaCliente::class, "agencia_cliente_id");
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingPlantilla::class, "plantilla_id");
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(AgenciaOnboardingRespuesta::class, "proyecto_id");
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(AgenciaOnboardingArchivo::class, "proyecto_id");
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(AgenciaOnboardingComentario::class, "proyecto_id");
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(AgenciaOnboardingEvento::class, "proyecto_id");
    }

    public function urlPublica(): string
    {
        return config("app.onboarding_url", "https://onboarding.bigstudio.cl") . "/o/" . $this->token;
    }

    public function estaActivo(): bool
    {
        if (in_array($this->estado, ["completado", "archivado"])) {
            return false;
        }
        if ($this->token_expira_en && $this->token_expira_en->isPast()) {
            return false;
        }
        return true;
    }
}
