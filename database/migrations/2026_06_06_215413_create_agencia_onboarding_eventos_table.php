<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_eventos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("agencia_onboarding_proyectos")->cascadeOnDelete();
            $table->string("tipo")->index()->comment("creado, enviado, abierto, seccion_completada, archivo_subido, completado, etc.");
            $table->text("descripcion")->nullable();
            $table->json("metadata")->nullable();
            $table->string("ip", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->timestamp("created_at")->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_eventos");
    }
};
