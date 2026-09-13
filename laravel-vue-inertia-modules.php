<?php

/**
 * ==============================================================================
 * MÓDULO DE MANTENIMIENTOS Y EQUIPOS: LARAVEL + INERTIA.JS + VUE 3
 * Sistema de Gestión de Mantenimientos Preventivos RILAZ
 * ==============================================================================
 * 
 * Este conjunto de archivos contiene los Controladores de Laravel, Rutas de Web,
 * y Vistas de Vue 3 (Composition API) para la visualización, registro en caliente
 * y búsqueda de mantenimientos y equipos.
 */

// ============================================================================= villa
// 1. RUTAS DE LARAVEL (routes/web.php)
// =============================================================================

/*
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\EquipoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Mantenimientos
    Route::get('/mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
    Route::get('/mantenimientos/nuevo', [MantenimientoController::class, 'create'])->name('mantenimientos.create');
    Route::post('/mantenimientos', [MantenimientoController::class, 'store'])->name('mantenimientos.store');
    Route::get('/mantenimientos/{mantenimiento}', [MantenimientoController::class, 'show'])->name('mantenimientos.show');
    Route::post('/mantenimientos/{mantenimiento}/marcar-impreso', [MantenimientoController::class, 'marcarImpreso'])->name('mantenimientos.marcar-impreso');

    // Búsqueda por Serie para Autocompletado en caliente
    Route::get('/api/equipos/buscar/{serie}', [EquipoController::class, 'buscarPorSerie'])->name('equipos.buscar');

    // Inventario de Equipos
    Route::get('/equipos', [EquipoController::class, 'index'])->name('equipos.index');
});
*/

// =============================================================================
// 2. CONTROLADOR DE MANTENIMIENTOS (app/Http/Controllers/MantenimientoController.php)
// =============================================================================

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Contrato;
use App\Models\MantenimientoChecklist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MantenimientoController extends Controller
{
    /**
     * Muestra la lista paginada de mantenimientos con filtros.
     */
    public function index(Request $request)
    {
        $query = Mantenimiento::with(['equipo', 'tecnico', 'contrato.cliente'])
            ->latest('fecha_mantenimiento');

        // Filtro por búsqueda (Serie, Inventario, Usuario, Técnico)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('equipo', function ($qe) use ($search) {
                    $qe->where('numero_serie', 'like', "%{$search}%")
                       ->orWhere('codigo_inventario', 'like', "%{$search}%")
                       ->orWhere('usuario_asignado', 'like', "%{$search}%")
                       ->orWhere('marca', 'like', "%{$search}%")
                       ->orWhere('modelo', 'like', "%{$search}%");
                })
                ->orWhereHas('tecnico', function ($qt) use ($search) {
                    $qt->where('nombre', 'like', "%{$search}%");
                });
            });
        }

        // Filtro por Tipo de Equipo
        if ($tipo = $request->input('tipo_equipo')) {
            $query->whereHas('equipo', function ($q) use ($tipo) {
                $q->where('tipo_equipo', $tipo);
            });
        }

        // Filtro por Estado de Impresión
        if ($request->has('impreso') && $request->input('impreso') !== '') {
            $query->where('impreso', $request->input('impreso'));
        }

        $mantenimientos = $query->paginate(15)->withQueryString();

        return Inertia::render('Mantenimientos/Index', [
            'mantenimientos' => $mantenimientos,
            'filters' => $request->only(['search', 'tipo_equipo', 'impreso']),
        ]);
    }

    /**
     * Formulario para crear un nuevo mantenimiento en sitio.
     */
    public function create()
    {
        // Contrato activo predeterminado para el técnico
        $contratoActivo = Contrato::with('cliente')
            ->where('estado', 'ACTIVO')
            ->first();

        return Inertia::render('Mantenimientos/Create', [
            'contratoActivo' => $contratoActivo,
        ]);
    }

    /**
     * Almacena el mantenimiento y registra/actualiza el equipo en caliente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contrato_id'         => 'required|exists:contratos,id',
            'numero_serie'        => 'required|string|max:100',
            'codigo_inventario'   => 'nullable|string|max:50',
            'tipo_equipo'         => 'required|in:DESKTOP,LAPTOP,IMPRESORA,OTRO',
            'marca'               => 'required|string|max:50',
            'modelo'              => 'required|string|max:50',
            'departamento_unidad' => 'nullable|string|max:100',
            'usuario_asignado'    => 'nullable|string|max:100',
            'observaciones'       => 'nullable|string',
            'recomendaciones'     => 'nullable|string',
            'checklists'          => 'nullable|array',
            'checklists.*.item'   => 'required|string',
            'checklists.*.hecho'  => 'required|boolean',
            'checklists.*.nota'   => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, &$mantenimiento) {
            // 1. Crear o actualizar datos del Equipo en caliente
            $equipo = Equipo::updateOrCreate(
                ['numero_serie' => trim($validated['numero_serie'])],
                [
                    'codigo_inventario'   => $validated['codigo_inventario'] ?? null,
                    'tipo_equipo'         => $validated['tipo_equipo'],
                    'marca'               => $validated['marca'],
                    'modelo'              => $validated['modelo'],
                    'departamento_unidad' => $validated['departamento_unidad'] ?? null,
                    'usuario_asignado'    => $validated['usuario_asignado'] ?? null,
                    'actualizado_por'     => Auth::id(),
                ]
            );

            // 2. Crear el Mantenimiento
            $mantenimiento = Mantenimiento::create([
                'contrato_id'         => $validated['contrato_id'],
                'equipo_id'           => $equipo->id,
                'tecnico_id'          => Auth::id() ?? 1,
                'fecha_mantenimiento' => now(),
                'observaciones'       => $validated['observaciones'] ?? null,
                'recomendaciones'     => $validated['recomendaciones'] ?? null,
                'estado_firma'        => 'PENDIENTE',
                'impreso'             => 0,
                'creado_por'          => Auth::id(),
            ]);

            // 3. Registrar ítems del Checklist
            if (!empty($validated['checklists'])) {
                foreach ($validated['checklists'] as $chk) {
                    MantenimientoChecklist::create([
                        'mantenimiento_id'  => $mantenimiento->id,
                        'item_verificacion' => $chk['item'],
                        'realizado'         => $chk['hecho'] ? 1 : 0,
                        'comentarios'       => $chk['nota'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('mantenimientos.index')
            ->with('success', '¡Mantenimiento registrado exitosamente!');
    }

    /**
     * Marca el reporte PDF como impreso.
     */
    public function marcarImpreso(Mantenimiento $mantenimiento)
    {
        $mantenimiento->update([
            'impreso'         => 1,
            'fecha_impresion' => now(),
            'impreso_por'     => Auth::id(),
            'estado_firma'    => 'FIRMADO_FISICO',
        ]);

        return back()->with('success', 'El reporte ha sido marcado como impreso.');
    }
}

// =============================================================================
// 3. CONTROLADOR DE EQUIPOS (app/Http/Controllers/EquipoController.php)
// =============================================================================

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipoController extends Controller
{
    /**
     * Lista global de inventario de equipos.
     */
    public function index(Request $request)
    {
        $query = Equipo::withCount('mantenimientos')->latest('updated_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_serie', 'like', "%{$search}%")
                  ->orWhere('codigo_inventario', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('usuario_asignado', 'like', "%{$search}%")
                  ->orWhere('departamento_unidad', 'like', "%{$search}%");
            });
        }

        if ($tipo = $request->input('tipo_equipo')) {
            $query->where('tipo_equipo', $tipo);
        }

        return Inertia::render('Equipos/Index', [
            'equipos' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'tipo_equipo']),
        ]);
    }

    /**
     * API Endpoint para buscar un equipo por número de serie en caliente.
     */
    public function buscarPorSerie($serie)
    {
        $equipo = Equipo::where('numero_serie', trim($serie))->first();

        if ($equipo) {
            return response()->json([
                'encontrado' => true,
                'equipo'     => $equipo,
            ]);
        }

        return response()->json([
            'encontrado' => false,
            'mensaje'    => 'Equipo no registrado anteriormente.',
        ]);
    }
}

/*
// =============================================================================
// 4. VISTA VUE 3: LISTADO DE MANTENIMIENTOS (resources/js/Pages/Mantenimientos/Index.vue)
// =============================================================================

<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    mantenimientos: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const tipoEquipo = ref(props.filters.tipo_equipo || '');
const impreso = ref(props.filters.impreso ?? '');

const aplicarFiltros = () => {
    router.get(route('mantenimientos.index'), {
        search: search.value,
        tipo_equipo: tipoEquipo.value,
        impreso: impreso.value,
    }, { preserveState: true, replace: true });
};

watch([search, tipoEquipo, impreso], () => {
    aplicarFiltros();
});
</script>

<template>
    <div class="p-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Registro de Mantenimientos</h1>
                    <p class="text-sm text-gray-500">Historial de intervenciones técnicas en campo</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <Link :href="route('mantenimientos.create')" 
                          class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition flex items-center gap-2">
                        <span>+</span> Nuevo Mantenimiento
                    </Link>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white p-4 rounded-xl shadow-sm mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <input v-model="search" type="text" placeholder="Buscar por Serie, Inventario, Usuario..." 
                       class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
                
                <select v-model="tipoEquipo" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500">
                    <option value="">Todos los tipos</option>
                    <option value="DESKTOP">Desktop</option>
                    <option value="LAPTOP">Laptop</option>
                    <option value="IMPRESORA">Impresora</option>
                    <option value="OTRO">Otro</option>
                </select>

                <select v-model="impreso" class="w-full border-gray-300 rounded-lg text-sm focus:ring-blue-500">
                    <option value="">Todos los estados de impresión</option>
                    <option value="1">Impresos (PDF)</option>
                    <option value="0">Pendientes de impresión</option>
                </select>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-600 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Serie / Inv.</th>
                            <th class="p-4">Equipo</th>
                            <th class="p-4">Usuario / Unidad</th>
                            <th class="p-4">Técnico</th>
                            <th class="p-4 text-center">Impresión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                        <tr v-for="item in mantenimientos.data" :key="item.id" class="hover:bg-gray-50">
                            <td class="p-4 text-xs font-medium text-gray-500">
                                {{ new Date(item.fecha_mantenimiento).toLocaleDateString('es-ES') }}
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-gray-900">{{ item.equipo?.numero_serie }}</div>
                                <div class="text-xs text-gray-400">{{ item.equipo?.codigo_inventario || 'Sin Inv.' }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 mr-2">
                                    {{ item.equipo?.tipo_equipo }}
                                </span>
                                {{ item.equipo?.marca }} {{ item.equipo?.modelo }}
                            </td>
                            <td class="p-4">
                                <div class="font-medium">{{ item.equipo?.usuario_asignado || 'N/A' }}</div>
                                <div class="text-xs text-gray-400">{{ item.equipo?.departamento_unidad || 'N/A' }}</div>
                            </td>
                            <td class="p-4 text-xs font-medium text-gray-600">
                                {{ item.tecnico?.nombre }}
                            </td>
                            <td class="p-4 text-center">
                                <span v-if="item.impreso" class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">
                                    ✓ Impreso
                                </span>
                                <span v-else class="px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700">
                                    ⏳ Pendiente
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

// =============================================================================
// 5. VISTA VUE 3: FORMULARIO DE REGISTRO EN SITIO (resources/js/Pages/Mantenimientos/Create.vue)
// =============================================================================

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    contratoActivo: Object,
});

const form = useForm({
    contrato_id: props.contratoActivo?.id || 1,
    numero_serie: '',
    codigo_inventario: '',
    tipo_equipo: 'DESKTOP',
    marca: '',
    modelo: '',
    departamento_unidad: '',
    usuario_asignado: '',
    observaciones: '',
    recomendaciones: '',
    checklists: [
        { item: 'Limpieza externa de soplado y brocha', hecho: true, nota: '' },
        { item: 'Limpieza interna de polvo y residuos', hecho: true, nota: '' },
        { item: 'Verificación de fuentes de poder y cables', hecho: true, nota: '' },
        { item: 'Prueba de encendido y funcionamiento de SO', hecho: true, nota: '' },
    ]
});

const buscandoSerie = ref(false);
const mensajeBusqueda = ref('');

// Búsqueda en caliente al ingresar la serie
const buscarEquipo = async () => {
    if (!form.numero_serie || form.numero_serie.length < 3) return;

    buscandoSerie.value = true;
    mensajeBusqueda.value = '';

    try {
        const response = await axios.get(route('equipos.buscar', form.numero_serie));
        if (response.data.encontrado) {
            const eq = response.data.equipo;
            form.codigo_inventario = eq.codigo_inventario || '';
            form.tipo_equipo = eq.tipo_equipo;
            form.marca = eq.marca;
            form.modelo = eq.modelo;
            form.departamento_unidad = eq.departamento_unidad || '';
            form.usuario_asignado = eq.usuario_asignado || '';
            mensajeBusqueda.value = '✓ Datos del equipo cargados automáticamente.';
        } else {
            mensajeBusqueda.value = 'ℹ Equipo nuevo. Ingresa las especificaciones.';
        }
    } catch (e) {
        console.error(e);
    } finally {
        buscandoSerie.value = false;
    }
};

const guardar = () => {
    form.post(route('mantenimientos.store'));
};
</script>

<template>
    <div class="p-6 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Formulario Único de Atención</h1>
            <p class="text-sm text-gray-500 mb-6">Registro de mantenimiento preventivo en sitio</p>

            <form @submit.prevent="guardar" class="space-y-6">
                <!-- Sección 1: Búsqueda por Serie -->
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <label class="block text-xs font-bold text-blue-800 uppercase mb-1">Número de Serie (Búsqueda en Caliente)</label>
                    <div class="flex gap-2">
                        <input v-model="form.numero_serie" @blur="buscarEquipo" type="text" placeholder="Ingresa o escanea la serie..."
                               class="flex-1 border-blue-300 rounded-lg focus:ring-blue-500 font-mono text-lg font-bold" required />
                    </div>
                    <span v-if="mensajeBusqueda" class="text-xs font-medium text-blue-700 mt-2 block">
                        {{ mensajeBusqueda }}
                    </span>
                </div>

                <!-- Sección 2: Datos del Equipo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Código Inventario</label>
                        <input v-model="form.codigo_inventario" type="text" class="w-full border-gray-300 rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Equipo</label>
                        <select v-model="form.tipo_equipo" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="DESKTOP">Desktop</option>
                            <option value="LAPTOP">Laptop</option>
                            <option value="IMPRESORA">Impresora</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Marca</label>
                        <input v-model="form.marca" type="text" class="w-full border-gray-300 rounded-lg text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Modelo</label>
                        <input v-model="form.modelo" type="text" class="w-full border-gray-300 rounded-lg text-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Unidad / Depto.</label>
                        <input v-model="form.departamento_unidad" type="text" class="w-full border-gray-300 rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Usuario Responsable</label>
                        <input v-model="form.usuario_asignado" type="text" class="w-full border-gray-300 rounded-lg text-sm" />
                    </div>
                </div>

                <!-- Sección 3: Checklist -->
                <div>
                    <h3 class="text-sm font-bold text-gray-700 uppercase mb-3">Lista de Verificación</h3>
                    <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
                        <div v-for="(item, idx) in form.checklists" :key="idx" class="flex flex-col md:flex-row md:items-center justify-between gap-2 p-2 bg-white rounded border border-gray-200">
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700">
                                <input type="checkbox" v-model="item.hecho" class="rounded text-blue-600 focus:ring-blue-500" />
                                {{ item.item }}
                            </label>
                            <input v-model="item.nota" type="text" placeholder="Comentarios u observaciones..." class="text-xs border-gray-300 rounded px-2 py-1 w-full md:w-64" />
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Observaciones del Servicio</label>
                    <textarea v-model="form.observaciones" rows="3" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Detalle adicional del trabajo realizado..."></textarea>
                </div>

                <!-- Botón de Guardar -->
                <div class="flex justify-end gap-3">
                    <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition">
                        Guardar Mantenimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
*/
