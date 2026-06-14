<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agencia_tarea_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_tarea_id')->constrained('agencia_tareas')->cascadeOnDelete();
            $table->foreignId('autor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autor_email', 180)->nullable()->comment('Fallback si el autor no tiene cuenta');
            $table->enum('rol', ['admin', 'colaborador', 'cliente'])->default('admin')->comment('Para etiquetar el autor en el hilo');
            $table->text('cuerpo');
            $table->string('enlace_externo', 500)->nullable()->comment('Link Drive/Figma cuando el comentario es un entregable por enlace');
            $table->timestamps();
        });

        Schema::create('agencia_tarea_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_tarea_id')->constrained('agencia_tareas')->cascadeOnDelete();
            $table->foreignId('agencia_tarea_comentario_id')->nullable()->constrained('agencia_tarea_comentarios')->nullOnDelete();
            $table->enum('tipo', ['brief', 'entregable'])->default('brief')->comment('brief = del admin; entregable = del disenador');
            $table->foreignId('subido_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_original', 255);
            $table->string('ruta', 255)->comment('Ruta relativa en disco public');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->default(0);
            $table->timestamps();
        });

        Schema::table('agencia_tarea_comparticiones', function (Blueprint $table) {
            if (!Schema::hasColumn('agencia_tarea_comparticiones', 'ultimo_visto_comentarios_en')) {
                $table->timestamp('ultimo_visto_comentarios_en')->nullable()->after('primer_acceso_en')->comment('Hasta donde leyo el hilo este colaborador');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencia_tarea_comparticiones', function (Blueprint $table) {
            if (Schema::hasColumn('agencia_tarea_comparticiones', 'ultimo_visto_comentarios_en')) {
                $table->dropColumn('ultimo_visto_comentarios_en');
            }
        });
        Schema::dropIfExists('agencia_tarea_archivos');
        Schema::dropIfExists('agencia_tarea_comentarios');
    }
};
