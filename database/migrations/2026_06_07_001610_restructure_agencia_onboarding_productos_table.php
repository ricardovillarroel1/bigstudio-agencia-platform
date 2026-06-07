<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop la tabla anterior (datos solo de prueba)
        Schema::dropIfExists("agencia_onboarding_productos");

        // Nueva estructura: 1 fila = 1 producto del cliente
        Schema::create("agencia_onboarding_productos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("proyecto_id")->constrained("agencia_onboarding_proyectos")->cascadeOnDelete();
            $table->string("seccion_key")->index();
            $table->string("campo_key");

            // Identidad del producto
            $table->string("titulo");
            $table->text("descripcion")->nullable();
            $table->string("vendor")->nullable();
            $table->string("categoria")->nullable()->comment("Product category de Shopify");
            $table->string("tipo")->nullable()->comment("Type, ej. T-Shirt");
            $table->string("tags")->nullable()->comment("Tags separados por coma");
            $table->boolean("publicado")->default(true);
            $table->enum("estado", ["active", "draft", "archived"])->default("active");

            // SEO
            $table->string("seo_title")->nullable();
            $table->text("seo_description")->nullable();

            // Imagen principal (FK al archivo subido)
            $table->foreignId("imagen_archivo_id")->nullable()
                ->constrained("agencia_onboarding_archivos")->nullOnDelete();
            $table->string("imagen_alt")->nullable();

            // Opciones (Talla, Color, Material) — Shopify permite hasta 3
            $table->string("opcion1_nombre")->nullable();
            $table->json("opcion1_valores")->nullable();
            $table->string("opcion2_nombre")->nullable();
            $table->json("opcion2_valores")->nullable();
            $table->string("opcion3_nombre")->nullable();
            $table->json("opcion3_valores")->nullable();

            // Variantes (combinacion cartesiana de opciones con precio/stock/etc)
            $table->json("variantes")->comment("Array de variantes: cada una con sku, precio, stock, peso, etc.");

            // Logistica
            $table->boolean("requiere_envio")->default(true);
            $table->boolean("es_gift_card")->default(false);

            $table->unsignedInteger("orden")->default(0);
            $table->timestamps();

            $table->index(["proyecto_id", "seccion_key", "campo_key"], "agencia_ob_prod_busqueda");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("agencia_onboarding_productos");
    }
};
