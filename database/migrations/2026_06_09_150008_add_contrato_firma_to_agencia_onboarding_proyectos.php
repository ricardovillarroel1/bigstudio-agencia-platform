<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->foreignId("contrato_plantilla_id")->nullable()->after("plantilla_id")
                ->constrained("agencia_contrato_plantillas")->nullOnDelete();
            $table->timestamp("contrato_firmado_at")->nullable()->after("contrato_plantilla_id");
            $table->string("contrato_firmante")->nullable()->after("contrato_firmado_at");
            $table->string("contrato_firma_ip", 45)->nullable()->after("contrato_firmante");
        });
    }

    public function down(): void
    {
        Schema::table("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->dropConstrainedForeignId("contrato_plantilla_id");
            $table->dropColumn(["contrato_firmado_at", "contrato_firmante", "contrato_firma_ip"]);
        });
    }
};
