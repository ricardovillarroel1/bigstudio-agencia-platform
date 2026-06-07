<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend facturas_servicio for detailed billing
        Schema::table('facturas_servicio', function (Blueprint $table) {
            $table->integer('documentos_incluidos')->default(0)->after('concepto');
            $table->integer('documentos_emitidos')->default(0)->after('documentos_incluidos');
            $table->integer('documentos_extra')->default(0)->after('documentos_emitidos');
            $table->decimal('precio_extra_uf', 10, 4)->default(0)->after('documentos_extra');
            $table->decimal('monto_extra_clp', 12, 0)->default(0)->after('precio_extra_uf');
            $table->decimal('monto_plan_clp', 12, 0)->default(0)->after('monto_extra_clp');
            $table->decimal('monto_neto', 12, 0)->default(0)->after('monto');
            $table->decimal('monto_iva', 12, 0)->default(0)->after('monto_neto');
            $table->string('tipo')->default('ciclo')->after('estado'); // ciclo, extra, manual
            $table->integer('lioren_factura_id')->nullable()->after('tipo');
            $table->integer('folio')->nullable()->after('lioren_factura_id');
            $table->longText('pdf_base64')->nullable()->after('folio');
            $table->string('flow_token')->nullable()->after('pdf_base64');
            $table->timestamp('pagada_at')->nullable()->after('flow_token');
            $table->decimal('valor_uf_usado', 12, 2)->default(0)->after('pagada_at');
        });

        // 2. Add pause field to suscripciones
        Schema::table('suscripciones', function (Blueprint $table) {
            $table->boolean('pausada')->default(false)->after('estado');
            $table->timestamp('pausada_at')->nullable()->after('pausada');
            $table->string('motivo_pausa')->nullable()->after('pausada_at');
        });

        // 3. Add billing_required flag to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('datos_facturacion_completos')->default(false)->after('role');
        });

        // 4. Ensure clientes table has razon_social field (empresa might be used differently)
        if (!Schema::hasColumn('clientes', 'razon_social')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('razon_social')->nullable()->after('empresa');
            });
        }
    }

    public function down(): void
    {
        Schema::table('facturas_servicio', function (Blueprint $table) {
            $table->dropColumn([
                'documentos_incluidos', 'documentos_emitidos', 'documentos_extra',
                'precio_extra_uf', 'monto_extra_clp', 'monto_plan_clp',
                'monto_neto', 'monto_iva', 'tipo', 'lioren_factura_id', 'folio',
                'pdf_base64', 'flow_token', 'pagada_at', 'valor_uf_usado'
            ]);
        });

        Schema::table('suscripciones', function (Blueprint $table) {
            $table->dropColumn(['pausada', 'pausada_at', 'motivo_pausa']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('datos_facturacion_completos');
        });

        if (Schema::hasColumn('clientes', 'razon_social')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('razon_social');
            });
        }
    }
};
