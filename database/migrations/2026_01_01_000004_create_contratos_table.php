<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->integer('meta_equipos_total')->nullable();
            $table->integer('mantenimientos_por_equipo')->default(1);
            $table->date('fecha_inicio');
            $table->date('fecha_limite')->nullable();
            $table->string('ubicacion_general', 255)->nullable();
            $table->text('requerimientos_especiales')->nullable();
            $table->enum('estado', ['ACTIVO', 'FINALIZADO', 'CANCELADO'])->default('ACTIVO');
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('contratos');
    }
};
