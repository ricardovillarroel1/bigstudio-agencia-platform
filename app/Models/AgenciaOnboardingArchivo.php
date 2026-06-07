<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AgenciaOnboardingArchivo extends Model
{
    protected $table = "agencia_onboarding_archivos";

    protected $fillable = [
        "proyecto_id",
        "seccion_key",
        "campo_key",
        "nombre_original",
        "ruta",
        "mime_type",
        "tamano_bytes",
        "drive_file_id",
        "subido_a_drive_en",
    ];

    protected $casts = [
        "subido_a_drive_en" => "datetime",
        "tamano_bytes" => "integer",
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingProyecto::class, "proyecto_id");
    }

    public function urlPublica(): string
    {
        return Storage::disk("public")->url($this->ruta);
    }

    public function tamanoLegible(): string
    {
        $b = $this->tamano_bytes;
        if ($b < 1024) return $b . " B";
        if ($b < 1048576) return round($b / 1024, 1) . " KB";
        if ($b < 1073741824) return round($b / 1048576, 1) . " MB";
        return round($b / 1073741824, 2) . " GB";
    }
}
