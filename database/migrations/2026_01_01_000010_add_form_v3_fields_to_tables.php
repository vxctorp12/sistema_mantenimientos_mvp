<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('equipos', function (Blueprint $table) {
            if (!Schema::hasColumn('equipos', 'direccion_ip')) {
                $table->string('direccion_ip', 45)->nullable()->after('usuario_asignado');
            }
            if (!Schema::hasColumn('equipos', 'sistema_operativo')) {
                $table->string('sistema_operativo', 50)->nullable()->after('direccion_ip');
            }
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimientos', 'contador_bn')) {
                $table->integer('contador_bn')->nullable()->after('recomendaciones');
            }
            if (!Schema::hasColumn('mantenimientos', 'contador_color')) {
                $table->integer('contador_color')->nullable()->after('contador_bn');
            }
        });

        Schema::table('mantenimiento_checklists', function (Blueprint $table) {
            if (!Schema::hasColumn('mantenimiento_checklists', 'categoria_seccion')) {
                $table->string('categoria_seccion', 50)->nullable()->after('mantenimiento_id');
            }
            if (!Schema::hasColumn('mantenimiento_checklists', 'estado')) {
                $table->string('estado', 20)->default('SI')->after('item_verificacion');
            }
            if (!Schema::hasColumn('mantenimiento_checklists', 'comentario')) {
                $table->string('comentario', 255)->nullable()->after('estado');
            }
        });
    }

    public function down(): void {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['direccion_ip', 'sistema_operativo']);
        });

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropColumn(['contador_bn', 'contador_color']);
        });

        Schema::table('mantenimiento_checklists', function (Blueprint $table) {
            $table->dropColumn(['categoria_seccion', 'estado', 'comentario']);
        });
    }
};
