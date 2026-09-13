<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->constrained('contratos')->restrictOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('tecnico_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->dateTime('fecha_mantenimiento')->useCurrent();
            $table->text('observaciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->enum('estado_firma', ['PENDIENTE', 'FIRMADO_FISICO'])->default('PENDIENTE');
            
            // Control de Impresión
            $table->boolean('impreso')->default(false)->index();
            $table->dateTime('fecha_impresion')->nullable();
            $table->foreignId('impreso_por')->nullable()->constrained('users')->nullOnDelete();
            
            // Auditoría
            $table->timestamp('creado_en')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            
            $table->index('contrato_id');
            $table->index('equipo_id');
            $table->index('tecnico_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mantenimientos');
    }
};
