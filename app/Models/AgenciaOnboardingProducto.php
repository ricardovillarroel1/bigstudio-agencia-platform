<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgenciaOnboardingProducto extends Model
{
    protected $table = "agencia_onboarding_productos";

    protected $fillable = [
        "proyecto_id",
        "seccion_key",
        "campo_key",
        "titulo",
        "descripcion",
        "vendor",
        "categoria",
        "tipo",
        "tags",
        "publicado",
        "estado",
        "seo_title",
        "seo_description",
        "imagen_archivo_id",
        "imagen_alt",
        "opcion1_nombre",
        "opcion1_valores",
        "opcion2_nombre",
        "opcion2_valores",
        "opcion3_nombre",
        "opcion3_valores",
        "variantes",
        "requiere_envio",
        "es_gift_card",
        "orden",
    ];

    protected $casts = [
        "publicado" => "boolean",
        "requiere_envio" => "boolean",
        "es_gift_card" => "boolean",
        "opcion1_valores" => "array",
        "opcion2_valores" => "array",
        "opcion3_valores" => "array",
        "variantes" => "array",
        "orden" => "integer",
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingProyecto::class, "proyecto_id");
    }

    public function imagen(): BelongsTo
    {
        return $this->belongsTo(AgenciaOnboardingArchivo::class, "imagen_archivo_id");
    }

    public function urlHandle(): string
    {
        return Str::slug($this->titulo) ?: ("producto-" . $this->id);
    }

    public function cantidadVariantes(): int
    {
        return is_array($this->variantes) ? count($this->variantes) : 0;
    }

    public function precioMin(): ?float
    {
        $vs = collect($this->variantes ?? [])->pluck("precio")->filter(fn($p) => is_numeric($p));
        return $vs->isEmpty() ? null : (float)$vs->min();
    }

    public function precioMax(): ?float
    {
        $vs = collect($this->variantes ?? [])->pluck("precio")->filter(fn($p) => is_numeric($p));
        return $vs->isEmpty() ? null : (float)$vs->max();
    }

    public function stockTotal(): int
    {
        return (int) collect($this->variantes ?? [])->sum("stock");
    }
}
