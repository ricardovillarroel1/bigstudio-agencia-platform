<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add retry columns to boletas table
        Schema::table('boletas', function (Blueprint $table) {
            if (!Schema::hasColumn('boletas', 'retry_count')) {
                $table->unsignedInteger('retry_count')->nullable()->default(0)->after('error_message');
            }
            if (!Schema::hasColumn('boletas', 'last_retry_at')) {
                $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            }
        });

        // Add retry columns to facturas_emitidas table
        Schema::table('facturas_emitidas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas_emitidas', 'retry_count')) {
                $table->unsignedInteger('retry_count')->nullable()->default(0)->after('error_message');
            }
            if (!Schema::hasColumn('facturas_emitidas', 'last_retry_at')) {
                $table->timestamp('last_retry_at')->nullable()->after('retry_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletas', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'last_retry_at']);
        });

        Schema::table('facturas_emitidas', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'last_retry_at']);
        });
    }
};
