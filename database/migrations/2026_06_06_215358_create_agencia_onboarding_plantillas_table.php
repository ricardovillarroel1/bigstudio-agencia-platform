<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("agencia_onboarding_plantillas", function (Blueprint $table) {
            $table->id();
            $table->string("nombre");
            $table->string("slug")->unique();
            $table->string("tipo_servicio")->index()->comment("shopify_prototipo, shopify_produccion, meta_ads, seo, mantencion, otro");
            $table->text("descripcion")->nullable();
            $table->integer("dias_habiles_estimados")->default(20);
            $table->json("secciones")->comment("Estructura del wizard: secciones, campos, tipos, validaciones");
            $table->boolean("activo")->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_plantillas");
    }
};
