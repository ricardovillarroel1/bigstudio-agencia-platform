<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_archivos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("agencia_onboarding_proyectos")->cascadeOnDelete();
            $table->string("seccion_key")->index();
            $table->string("campo_key");
            $table->string("nombre_original");
            $table->string("ruta")->comment("Ruta relativa en storage/app/public/agencia_onboarding/");
            $table->string("mime_type")->nullable();
            $table->unsignedBigInteger("tamano_bytes")->default(0);
            $table->string("drive_file_id")->nullable()->comment("Para sync futuro a Google Drive");
            $table->timestamp("subido_a_drive_en")->nullable();
            $table->timestamps();
            $table->index(["proyecto_id", "seccion_key", "campo_key"], "agencia_ob_archivo_busqueda");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_archivos");
    }
};
