<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_respuestas", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("agencia_onboarding_proyectos")->cascadeOnDelete();
            $table->string("seccion_key")->index()->comment("Key de la seccion: identidad_visual, contenido, etc.");
            $table->string("campo_key")->comment("Key del campo dentro de la seccion: logo_principal, paleta, etc.");
            $table->longText("valor")->nullable()->comment("Valor: texto, JSON, lista, etc. segun el tipo de campo");
            $table->timestamps();
            $table->unique(["proyecto_id", "seccion_key", "campo_key"], "agencia_ob_respuesta_unique");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_respuestas");
    }
};
