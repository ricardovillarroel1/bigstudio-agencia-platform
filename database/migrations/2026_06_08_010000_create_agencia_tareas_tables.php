<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agencia_tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_cliente_id')->constrained('agencia_clientes')->cascadeOnDelete();
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['borrador', 'pendiente', 'en_curso', 'terminado'])->default('pendiente')->index();
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
            $table->date('fecha_limite')->nullable();
            $table->timestamp('terminada_en')->nullable()->comment('Se setea al pasar a estado terminado');
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete()->comment('Admin que creo la tarea');
            $table->timestamps();
        });

        Schema::create('agencia_tarea_comparticiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agencia_tarea_id')->constrained('agencia_tareas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Colaborador con quien se compartio (si tiene cuenta)');
            $table->string('email', 180)->comment('Email al que se compartio la tarea');
            $table->timestamp('compartida_en')->nullable();
            $table->timestamp('primer_acceso_en')->nullable()->comment('Cuando el destinatario la abrio por primera vez');
            $table->timestamps();
            $table->unique(['agencia_tarea_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencia_tarea_comparticiones');
        Schema::dropIfExists('agencia_tareas');
    }
};
