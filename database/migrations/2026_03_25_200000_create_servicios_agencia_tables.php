<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clientes de la agencia (NO son usuarios del sistema, solo registros internos)
        Schema::create('agencia_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('rut')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('giro')->nullable();
            $table->string('direccion_fiscal')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('region')->nullable();
            $table->string('comuna')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });

        // 2. Catálogo de servicios de la agencia
        Schema::create('agencia_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // ej: "Manejo de anuncios Meta Ads"
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 0)->default(0); // precio en CLP
            $table->enum('periodicidad', ['mensual', 'trimestral', 'semestral', 'anual', 'unico'])->default('mensual');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 3. Asignación de servicios a clientes (con info de inversión)
        Schema::create('agencia_cliente_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_cliente_id')->constrained('agencia_clientes')->onDelete('cascade');
            $table->foreignId('agencia_servicio_id')->constrained('agencia_servicios')->onDelete('cascade');
            $table->decimal('precio_acordado', 12, 0)->default(0); // precio específico para este cliente
            $table->decimal('inversion_publicidad', 12, 0)->nullable(); // monto que invierte en ads (info interna)
            $table->string('plataforma_publicidad')->nullable(); // ej: "Meta Ads", "Google Ads"
            $table->text('notas_internas')->nullable();
            $table->enum('estado', ['activo', 'pausado', 'cancelado'])->default('activo');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });

        // 4. Planes/suscripciones de servicios de agencia
        Schema::create('agencia_suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_cliente_id')->constrained('agencia_clientes')->onDelete('cascade');
            $table->foreignId('agencia_cliente_servicio_id')->nullable()->constrained('agencia_cliente_servicio')->onDelete('set null');
            $table->string('concepto'); // descripción del cobro
            $table->decimal('monto', 12, 0); // monto en CLP
            $table->enum('periodicidad', ['mensual', 'trimestral', 'semestral', 'anual'])->default('mensual');
            $table->enum('estado', ['activa', 'pausada', 'cancelada', 'vencida'])->default('activa');
            $table->date('fecha_inicio');
            $table->date('proximo_cobro');
            $table->date('fecha_fin')->nullable();
            $table->boolean('facturacion_automatica')->default(true);
            $table->integer('dias_anticipacion_factura')->default(5); // emitir factura X días antes del vencimiento
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('factura_ciclo_emitida')->default(false);
            $table->timestamps();
        });

        // 5. Cobros de servicios de agencia
        Schema::create('agencia_cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_cliente_id')->constrained('agencia_clientes')->onDelete('cascade');
            $table->foreignId('agencia_suscripcion_id')->nullable()->constrained('agencia_suscripciones')->onDelete('set null');
            $table->string('concepto');
            $table->decimal('monto', 12, 0);
            $table->enum('estado', ['pendiente', 'pagado', 'anulado', 'vencido'])->default('pendiente');
            $table->enum('metodo_pago', ['transferencia', 'flow', 'otro'])->nullable();
            $table->string('flow_token')->nullable();
            $table->string('comprobante_path')->nullable();
            $table->string('comprobante_original_name')->nullable();
            $table->text('notas_admin')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamp('vence_at')->nullable();
            // Datos de facturación Lioren
            $table->string('lioren_folio')->nullable();
            $table->string('lioren_tipo_doc')->nullable(); // 33=factura, 39=boleta
            $table->text('lioren_pdf_url')->nullable();
            $table->text('lioren_xml_url')->nullable();
            $table->enum('factura_estado', ['no_emitida', 'emitida', 'error'])->default('no_emitida');
            $table->text('factura_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencia_cobros');
        Schema::dropIfExists('agencia_suscripciones');
        Schema::dropIfExists('agencia_cliente_servicio');
        Schema::dropIfExists('agencia_servicios');
        Schema::dropIfExists('agencia_clientes');
    }
};
