<?php

/**
 * ==============================================================================
 * MÓDULO DE DASHBOARD DE MÉTRICAS Y AVANCE DE CONTRATO
 * Sistema de Gestión de Mantenimientos Preventivos RILAZ
 * Laravel 11 + Inertia.js + Vue 3 (Composition API) + Tailwind CSS
 * ==============================================================================
 * 
 * Contenido del archivo:
 * 1. Controlador Laravel: app/Http/Controllers/DashboardController.php
 * 2. Vista Vue 3: resources/js/Pages/Dashboard.vue
 * 3. Rutas de Laravel: routes/web.php
 */

// ==============================================================================
// 1. CONTROLADOR LARAVEL (app/Http/Controllers/DashboardController.php)
// ==============================================================================

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Muestra el panel principal de métricas de avance del contrato activo.
     */
    public function index(Request $request)
    {
        // 1. Obtener lista de contratos activos para el selector
        $contratosActivos = Contrato::with('cliente')
            ->where('estado', 'ACTIVO')
            ->get();

        // 2. Seleccionar el contrato solicitado o el primero activo por defecto
        $contratoId = $request->input('contrato_id');
        $contrato = $contratosActivos->firstWhere('id', $contratoId) ?? $contratosActivos->first();

        if (!$contrato) {
            return Inertia::render('Dashboard', [
                'contratos' => [],
                'contratoSeleccionado' => null,
                'metricas' => null
            ]);
        }

        // Cargar metas desglosadas por tipo si existen
        $contrato->load('metasTipo');

        // 3. Cálculos de Metas y Avance Global
        $metaEquiposTotal = $contrato->meta_equipos_total ?? 0;
        $frecuenciaServicio = $contrato->mantenimientos_por_equipo ?? 1;
        $metaMantenimientosTotal = $metaEquiposTotal * $frecuenciaServicio;

        // Total de mantenimientos realizados en este contrato
        $totalRealizados = Mantenimiento::where('contrato_id', $contrato->id)->count();
        $porcentajeAvanceGlobal = $metaMantenimientosTotal > 0 
            ? round(($totalRealizados / $metaMantenimientosTotal) * 100, 1) 
            : 0;

        // 4. Métricas de Impresión y Control Operativo
        $totalImpresos = Mantenimiento::where('contrato_id', $contrato->id)
            ->where('impreso', 1)
            ->count();
            
        $pendientesImpresion = $totalRealizados - $totalImpresos;

        // 5. Equipos Únicos Atendidos vs Meta
        $equiposUnicosAtendidos = Mantenimiento::where('contrato_id', $contrato->id)
            ->distinct('equipo_id')
            ->count('equipo_id');

        // 6. Desglose de Atenciones por Tipo de Equipo
        $atencionesPorTipoRaw = DB::table('mantenimientos')
            ->join('equipos', 'mantenimientos.equipo_id', '=', 'equipos.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select('equipos.tipo_equipo', DB::raw('count(*) as total'))
            ->groupBy('equipos.tipo_equipo')
            ->pluck('total', 'tipo_equipo')
            ->toArray();

        $tiposDisponibles = ['DESKTOP', 'LAPTOP', 'IMPRESORA', 'OTRO'];
        $desglosePorTipo = [];

        foreach ($tiposDisponibles as $tipo) {
            $metaTipoObj = $contrato->metasTipo->firstWhere('tipo_equipo', $tipo);
            $metaTipoCantidad = $metaTipoObj ? ($metaTipoObj->cantidad_meta * $frecuenciaServicio) : 0;
            $realizadosTipo = $atencionesPorTipoRaw[$tipo] ?? 0;

            $desglosePorTipo[] = [
                'tipo' => $tipo,
                'realizados' => $realizadosTipo,
                'meta' => $metaTipoCantidad,
                'porcentaje' => $metaTipoCantidad > 0 ? round(($realizadosTipo / $metaTipoCantidad) * 100, 1) : 0
            ];
        }

        // 7. Rendimiento y Productividad por Técnico
        $productividadTecnicos = DB::table('mantenimientos')
            ->join('usuarios', 'mantenimientos.tecnico_id', '=', 'usuarios.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select(
                'usuarios.id',
                'usuarios.nombre',
                DB::raw('count(mantenimientos.id) as total_atenciones'),
                DB::raw('sum(mantenimientos.impreso) as total_impresos')
            )
            ->groupBy('usuarios.id', 'usuarios.nombre')
            ->orderByDesc('total_atenciones')
            ->get();

        // 8. Últimos Mantenimientos Registrados (Actividad Reciente)
        $ultimosMantenimientos = Mantenimiento::with(['equipo', 'tecnico'])
            ->where('contrato_id', $contrato->id)
            ->orderByDesc('fecha_mantenimiento')
            ->take(6)
            ->get()
            ->map(function ($mtto) {
                return [
                    'id' => $mtto->id,
                    'fecha' => $mtto->fecha_mantenimiento ? $mtto->fecha_mantenimiento->format('d/m/Y H:i') : 'N/A',
                    'tipo_equipo' => $mtto->equipo->tipo_equipo ?? 'OTRO',
                    'equipo_serie' => $mtto->equipo->numero_serie ?? 'S/N',
                    'equipo_inventario' => $mtto->equipo->codigo_inventario ?? 'N/A',
                    'marca_modelo' => ($mtto->equipo->marca ?? '') . ' ' . ($mtto->equipo->modelo ?? ''),
                    'tecnico_nombre' => $mtto->tecnico->nombre ?? 'Sin asignar',
                    'impreso' => (bool)$mtto->impreso,
                    'estado_firma' => $mtto->estado_firma
                ];
            });

        return Inertia::render('Dashboard', [
            'contratos' => $contratosActivos,
            'contratoSeleccionado' => [
                'id' => $contrato->id,
                'cliente_nombre' => $contrato->cliente->nombre_cliente ?? $contrato->cliente->nombre_empresa ?? 'Cliente',
                'ubicacion' => $contrato->ubicacion_general,
                'fecha_inicio' => $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A',
                'fecha_limite' => $contrato->fecha_limite ? $contrato->fecha_limite->format('d/m/Y') : 'Sin fecha límite',
            ],
            'metricas' => [
                'meta_equipos' => $metaEquiposTotal,
                'frecuencia' => $frecuenciaServicio,
                'meta_mantenimientos_total' => $metaMantenimientosTotal,
                'total_realizados' => $totalRealizados,
                'porcentaje_avance_global' => $porcentajeAvanceGlobal,
                'equipos_unicos_atendidos' => $equiposUnicosAtendidos,
                'total_impresos' => $totalImpresos,
                'pendientes_impresion' => $pendientesImpresion,
                'desglose_por_tipo' => $desglosePorTipo,
                'productividad_tecnicos' => $productividadTecnicos,
                'ultimos_mantenimientos' => $ultimosMantenimientos
            ]
        ]);
    }
}


/*
// ==============================================================================
// 2. VISTA VUE 3 (resources/js/Pages/Dashboard.vue)
// ==============================================================================
<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    contratos: Array,
    contratoSeleccionado: Object,
    metricas: Object
});

const contratoId = ref(props.contratoSeleccionado?.id || '');

// Cambiar de contrato al seleccionar en el dropdown
watch(contratoId, (nuevoId) => {
    if (nuevoId) {
        router.get(route('dashboard'), { contrato_id: nuevoId }, { preserveState: true });
    }
});

// Ayudante para colores de badge según tipo de equipo
const getBadgeClass = (tipo) => {
    switch (tipo) {
        case 'DESKTOP': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'LAPTOP': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'IMPRESORA': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>

<template>
    <AuthenticatedLayout title="Dashboard de Mantenimientos">
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Encabezado con Selector de Contrato -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard de Mantenimientos RILAZ</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Cliente: <span class="font-semibold text-gray-700">{{ contratoSeleccionado?.cliente_nombre }}</span> 
                        | Ubicación: <span class="font-medium text-gray-600">{{ contratoSeleccionado?.ubicacion || 'General' }}</span>
                    </p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <label for="contrato" class="text-sm font-medium text-gray-700 whitespace-nowrap">Contrato Activo:</label>
                    <select 
                        id="contrato" 
                        v-model="contratoId" 
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-medium"
                    >
                        <option v-for="c in contratos" :key="c.id" :value="c.id">
                            {{ c.cliente?.nombre_cliente || c.cliente?.nombre_empresa }} ({{ c.ubicacion_general || 'Sede Principal' }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Si no hay métricas disponibles -->
            <div v-if="!metricas" class="bg-white p-12 text-center rounded-xl shadow-sm border border-gray-100 text-gray-500">
                No hay contratos activos registrados en el sistema.
            </div>

            <template v-else>
                <!-- Tarjetas Principales de KPI -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- KPI 1: Avance Global del Contrato -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Avance del Contrato</span>
                            <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-baseline space-x-2">
                                <span class="text-3xl font-extrabold text-gray-900">{{ metricas.porcentaje_avance_global }}%</span>
                                <span class="text-xs text-gray-500 font-medium">de la meta</span>
                            </div>
                            <!-- Barra de Progreso -->
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3 overflow-hidden">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: metricas.porcentaje_avance_global + '%' }"></div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 font-medium">
                            {{ metricas.total_realizados }} de {{ metricas.meta_mantenimientos_total }} servicios completados
                        </p>
                    </div>

                    <!-- KPI 2: Total Mantenimientos Realizados -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Atenciones Realizadas</span>
                            <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <span class="text-3xl font-extrabold text-gray-900">{{ metricas.total_realizados }}</span>
                            <p class="text-xs text-emerald-600 font-semibold mt-1">
                                {{ metricas.equipos_unicos_atendidos }} equipos físicos intervenidos
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 font-medium">
                            Frecuencia: {{ metricas.frecuencia }} mtto(s) por equipo
                        </p>
                    </div>

                    <!-- KPI 3: Control de Impresiones PDF -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Hojas Impresas / Firma</span>
                            <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-baseline space-x-2">
                                <span class="text-3xl font-extrabold text-gray-900">{{ metricas.total_impresos }}</span>
                                <span class="text-xs text-gray-500 font-medium">impresos</span>
                            </div>
                            <span v-if="metricas.pendientes_impresion > 0" class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                ⚠️ {{ metricas.pendientes_impresion }} pendientes de imprimir
                            </span>
                            <span v-else class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                ✓ Todo impreso al día
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 font-medium">
                            Comprobantes de servicio emitidos en sitio
                        </p>
                    </div>

                    <!-- KPI 4: Meta total del contrato -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Meta Total Equipos</span>
                            <span class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </span>
                        </div>
                        <div class="mt-4">
                            <span class="text-3xl font-extrabold text-gray-900">{{ metricas.meta_equipos }}</span>
                            <span class="text-xs text-gray-500 block mt-1 font-medium">equipos contratados</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-3 font-medium">
                            Inicio: {{ contratoSeleccionado?.fecha_inicio }}
                        </p>
                    </div>

                </div>

                <!-- Sección Intermedia: Desglose por Tipo de Hardware & Productividad de Técnicos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Desglose por Tipo de Equipo -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
                            <span>Avance por Categoria de Equipo</span>
                            <span class="text-xs font-normal text-gray-500">Realizados vs Meta</span>
                        </h2>
                        
                        <div class="space-y-4">
                            <div v-for="item in metricas.desglose_por_tipo" :key="item.tipo" class="border-b border-gray-50 pb-3 last:border-0 last:pb-0">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded border" :class="getBadgeClass(item.tipo)">
                                        {{ item.tipo }}
                                    </span>
                                    <div class="text-xs font-semibold text-gray-700">
                                        {{ item.realizados }} <span v-if="item.meta > 0" class="text-gray-400">/ {{ item.meta }}</span>
                                        <span v-if="item.meta > 0" class="ml-1 text-blue-600 font-bold">({{ item.porcentaje }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div 
                                        class="h-2 rounded-full bg-blue-500 transition-all duration-500"
                                        :style="{ width: (item.meta > 0 ? Math.min(item.porcentaje, 100) : (item.realizados > 0 ? 100 : 0)) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ranking de Productividad por Técnico -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
                            <span>Productividad del Personal Técnico</span>
                            <span class="text-xs font-normal text-gray-500">Atenciones en Campo</span>
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">Técnico Encargado</th>
                                        <th scope="col" class="px-4 py-3 text-center">Mantenimientos</th>
                                        <th scope="col" class="px-4 py-3 text-center">Impresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="tec in metricas.productividad_tecnicos" :key="tec.id" class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-semibold text-gray-900 flex items-center space-x-2">
                                            <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                                {{ tec.nombre.charAt(0) }}
                                            </div>
                                            <span>{{ tec.nombre }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-blue-600">
                                            {{ tec.total_atenciones }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ tec.total_impresos }} / {{ tec.total_atenciones }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!metricas.productividad_tecnicos.length">
                                        <td colspan="3" class="px-4 py-4 text-center text-gray-400">Sin atenciones registradas</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Tabla de Últimos Mantenimientos Registrados -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Actividad Reciente en Sitio</h2>
                            <p class="text-xs text-gray-500">Últimos mantenimientos preventivos ingresados al sistema</p>
                        </div>
                        <Link 
                            :href="route('mantenimientos.index')" 
                            class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800"
                        >
                            Ver todos los mantenimientos ➔
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Fecha</th>
                                    <th scope="col" class="px-6 py-3">Tipo</th>
                                    <th scope="col" class="px-6 py-3">Serie / Inventario</th>
                                    <th scope="col" class="px-6 py-3">Marca / Modelo</th>
                                    <th scope="col" class="px-6 py-3">Técnico</th>
                                    <th scope="col" class="px-6 py-3 text-center">Estado PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in metricas.ultimos_mantenimientos" :key="m.id" class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        {{ m.fecha }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded border" :class="getBadgeClass(m.tipo_equipo)">
                                            {{ m.tipo_equipo }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">
                                        <div class="font-bold text-gray-900">{{ m.equipo_serie }}</div>
                                        <div v-if="m.equipo_inventario !== 'N/A'" class="text-gray-400">Inv: {{ m.equipo_inventario }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 font-medium">
                                        {{ m.marca_modelo }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ m.tecnico_nombre }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="m.impreso" class="px-2.5 py-1 text-xs font-semibold text-emerald-800 bg-emerald-100 rounded-full inline-flex items-center gap-1">
                                            ✓ Impreso
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-xs font-semibold text-amber-800 bg-amber-100 rounded-full inline-flex items-center gap-1">
                                            ⏳ Pendiente
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!metricas.ultimos_mantenimientos.length">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                        No se registran atenciones recientes para este contrato.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </template>
        </div>
    </AuthenticatedLayout>
</template>
*/

// ==============================================================================
// 3. RUTAS DE LARAVEL (routes/web.php)
// ==============================================================================
/*
use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
*/
