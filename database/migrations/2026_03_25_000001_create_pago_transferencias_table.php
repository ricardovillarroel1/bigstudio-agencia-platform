<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_transferencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('periodo')->default('mensual'); // mensual o anual
            $table->integer('monto'); // Monto en CLP
            $table->string('comprobante_path'); // Ruta del archivo subido
            $table->string('comprobante_original_name')->nullable(); // Nombre original del archivo
            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->text('notas_admin')->nullable(); // Notas del admin al aprobar/rechazar
            $table->unsignedBigInteger('revisado_por')->nullable(); // Admin que revisó
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('planes')->onDelete('cascade');
            $table->foreign('revisado_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_transferencias');
    }
};
