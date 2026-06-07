<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->foreignId("logo_cliente_archivo_id")->nullable()->after("email_cliente")
                ->constrained("agencia_onboarding_archivos")->nullOnDelete()
                ->comment("Logo del cliente para personalizar su wizard");
            $table->string("video_bienvenida_url")->nullable()->after("logo_cliente_archivo_id")
                ->comment("URL de video Loom/YouTube de bienvenida para el cliente");
        });
    }

    public function down(): void
    {
        Schema::table("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->dropConstrainedForeignId("logo_cliente_archivo_id");
            $table->dropColumn("video_bienvenida_url");
        });
    }
};
