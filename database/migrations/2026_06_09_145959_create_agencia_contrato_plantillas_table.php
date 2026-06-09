<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_contrato_plantillas", function (Blueprint $table) {
            $table->id();
            $table->string("nombre");
            $table->string("slug")->unique();
            $table->string("tipo_servicio")->nullable()->index();
            $table->text("intro")->nullable()->comment("Parrafo introductorio");
            $table->json("clausulas")->comment("Array de {titulo, contenido} - admite mini-HTML");
            $table->text("cierre")->nullable()->comment("Texto de cierre antes de la firma");
            $table->boolean("activo")->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_contrato_plantillas");
    }
};
