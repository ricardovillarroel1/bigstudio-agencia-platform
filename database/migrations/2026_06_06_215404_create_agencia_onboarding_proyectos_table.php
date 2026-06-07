<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("agencia_cliente_id")->constrained("agencia_clientes")->cascadeOnDelete();
            $table->foreignId("plantilla_id")->constrained("agencia_onboarding_plantillas")->restrictOnDelete();
            $table->string("token", 64)->unique()->index()->comment("Token publico para acceso del cliente sin login");
            $table->string("titulo");
            $table->enum("estado", ["no_iniciado", "en_progreso", "completado", "archivado"])->default("no_iniciado")->index();
            $table->unsignedTinyInteger("porcentaje_avance")->default(0);
            $table->timestamp("fecha_envio")->nullable()->comment("Cuando se envio el email al cliente");
            $table->timestamp("fecha_primer_acceso")->nullable()->comment("Cuando el cliente abrio por primera vez");
            $table->timestamp("fecha_completado")->nullable();
            $table->timestamp("token_expira_en")->nullable();
            $table->text("notas_internas")->nullable()->comment("Notas para el equipo BigStudio, no visibles al cliente");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_proyectos");
    }
};
