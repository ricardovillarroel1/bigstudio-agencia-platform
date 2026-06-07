<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->string("email_cliente")->nullable()->after("titulo")->comment("Email del contacto del cliente para enviar la invitacion");
        });
    }

    public function down(): void
    {
        Schema::table("agencia_onboarding_proyectos", function (Blueprint $table) {
            $table->dropColumn("email_cliente");
        });
    }
};
