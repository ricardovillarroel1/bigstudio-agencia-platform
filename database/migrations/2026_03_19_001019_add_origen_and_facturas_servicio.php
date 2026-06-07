<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campo 'origen' a suscripciones para distinguir manual vs pago
        if (!Schema::hasColumn('suscripciones', 'origen')) {
            Schema::table('suscripciones', function (Blueprint $table) {
                $table->string('origen')->default('pago')->after('estado'); // 'pago' o 'manual'
            });
        }

        // Agregar campo 'limite_documentos' a planes (usa monthly_order_limit como referencia)
        if (!Schema::hasColumn('planes', 'limite_documentos')) {
            Schema::table('planes', function (Blueprint $table) {
                $table->integer('limite_documentos')->nullable()->after('monthly_order_limit');
            });
        }

        // Crear tabla facturas_servicio para las facturas de BigStudio al cliente
        if (!Schema::hasTable('facturas_servicio')) {
            Schema::create('facturas_servicio', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unsignedBigInteger('suscripcion_id')->nullable();
                $table->foreign('suscripcion_id')->references('id')->on('suscripciones')->onDelete('set null');
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->foreign('plan_id')->references('id')->on('planes')->onDelete('set null');
                $table->foreignId('payment_id')->nullable();
                $table->string('numero_factura')->nullable();
                $table->string('concepto')->nullable();
                $table->decimal('monto', 12, 2)->default(0);
                $table->string('moneda', 10)->default('CLP');
                $table->date('periodo_inicio')->nullable();
                $table->date('periodo_fin')->nullable();
                $table->string('estado')->default('pagada'); // pagada, pendiente
                $table->string('pdf_url')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('suscripciones', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn('limite_documentos');
        });
        Schema::dropIfExists('facturas_servicio');
    }
};
