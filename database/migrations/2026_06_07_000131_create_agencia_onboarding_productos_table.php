<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_productos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("agencia_onboarding_proyectos")->cascadeOnDelete();
            $table->foreignId("archivo_id")->nullable()->constrained("agencia_onboarding_archivos")->nullOnDelete()->comment("CSV original subido");
            $table->string("seccion_key")->index();
            $table->string("campo_key");

            $table->json("productos")->comment("Array de productos con sus variantes parseados del CSV");
            $table->unsignedInteger("total_productos")->default(0);
            $table->unsignedInteger("total_variantes")->default(0);

            $table->json("warnings")->nullable()->comment("Avisos no bloqueantes (ej: imagen sin URL)");
            $table->json("errores")->nullable()->comment("Errores que requieren accion del cliente (ej: SKU duplicado)");

            $table->timestamp("parseado_at")->nullable();
            $table->timestamps();

            $table->index(["proyecto_id", "seccion_key", "campo_key"], "agencia_ob_prod_busqueda");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_productos");
    }
};
