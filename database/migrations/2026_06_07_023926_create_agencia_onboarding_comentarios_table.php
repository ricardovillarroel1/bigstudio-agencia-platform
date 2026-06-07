<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_comentarios", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("agencia_onboarding_proyectos")->cascadeOnDelete();
            $table->string("seccion_key")->nullable()->comment("Seccion a la que aplica; null = general");
            $table->text("mensaje");
            $table->enum("autor", ["admin", "cliente"])->default("admin");
            $table->boolean("resuelto")->default(false);
            $table->timestamps();
            $table->index(["proyecto_id", "seccion_key"]);
        });

        // Estado nuevo: requiere_correcciones
        \DB::statement("ALTER TABLE agencia_onboarding_proyectos MODIFY COLUMN estado ENUM('no_iniciado','en_progreso','requiere_correcciones','completado','archivado') NOT NULL DEFAULT 'no_iniciado'");
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_comentarios");
        \DB::statement("ALTER TABLE agencia_onboarding_proyectos MODIFY COLUMN estado ENUM('no_iniciado','en_progreso','completado','archivado') NOT NULL DEFAULT 'no_iniciado'");
    }
};
