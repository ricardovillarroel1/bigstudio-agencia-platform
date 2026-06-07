<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->comment('Usuario admin que ejecutó la acción');
            $table->string('admin_email', 191)->nullable()->comment('Email denormalizado para registro histórico');
            $table->string('action', 64)->comment('marcar_pagada | emitir_dte | pausar | reanudar | reiniciar_ciclo');
            $table->string('target_type', 64)->nullable()->comment('factura_servicio | suscripcion');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable()->comment('Cliente afectado (para filtrar todas las acciones sobre un cliente)');
            $table->json('metadata')->nullable()->comment('Contexto adicional: antes/después, monto, folio, etc.');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('admin_id');
            $table->index('target_user_id');
            $table->index(['target_type', 'target_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
