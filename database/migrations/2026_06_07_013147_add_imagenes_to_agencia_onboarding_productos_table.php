<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("agencia_onboarding_productos", function (Blueprint $table) {
            // Galeria de imagenes: array ordenado de IDs de agencia_onboarding_archivos.
            // El primero es la imagen principal. imagen_archivo_id se mantiene
            // sincronizado con imagenes[0] para compatibilidad con thumbnails.
            $table->json("imagenes")->nullable()->after("imagen_archivo_id");
        });

        // Migrar datos existentes: si hay imagen_archivo_id, ponerlo como [id] en imagenes
        $productos = \DB::table("agencia_onboarding_productos")->whereNotNull("imagen_archivo_id")->get();
        foreach ($productos as $p) {
            \DB::table("agencia_onboarding_productos")
                ->where("id", $p->id)
                ->update(["imagenes" => json_encode([$p->imagen_archivo_id])]);
        }
    }

    public function down(): void
    {
        Schema::table("agencia_onboarding_productos", function (Blueprint $table) {
            $table->dropColumn("imagenes");
        });
    }
};
