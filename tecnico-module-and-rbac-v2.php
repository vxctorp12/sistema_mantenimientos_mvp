<?php
/**
 * ==============================================================================
 * MÓDULO DE TÉCNICOS Y RESTRICCIÓN DE ACCESOS POR ROLES (RBAC v2 - Bento Grid)
 * Sistema de Gestión de Mantenimientos Preventivos RILAZ
 * ==============================================================================
 * 
 * Novedades v2:
 * 1. Header simplificado: Se removieron los enlaces de navegación directa en la barra superior.
 * 2. Navegación en el Main (Bento Dashboard Grid): Los módulos se presentan en el área 
 *    principal como tarjetas visuales e interactivas adaptadas dinámicamente al ROL
 *    del usuario (ADMIN vs TÉCNICO vs INVITADO).
 * 3. Seguridad a nivel de middleware, controladores y políticas de Eloquent (RBAC).
 * 4. Control de edición propia para técnicos (solo edita sus propias atenciones).
 */

// ==============================================================================
// 1. NAVEGACIÓN Y LAYOUT CON HEADER LIMPIO (resources/js/Layouts/AuthenticatedLayout.vue)
// ==============================================================================
/*
<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation Header Limpio (Solo marca y menú de perfil) -->
        <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo / Identidad de la marca -->
                    <div class="flex items-center space-x-3">
                        <Link :href="route('dashboard')" class="flex items-center space-x-2">
                            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md">
                                R
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 leading-tight">RILAZ</span>
                                <span class="text-xs text-gray-500">Mantenimientos</span>
                            </div>
                        </Link>
                    </div>

                    <!-- Menú de Usuario y Rol -->
                    <div class="flex items-center space-x-4">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-semibold text-gray-800">{{ $page.props.auth.user.name }}</div>
                            <div class="text-xs flex justify-end">
                                <span :class="{
                                    'bg-purple-100 text-purple-700': $page.props.auth.user.rol === 'ADMIN',
                                    'bg-blue-100 text-blue-700': $page.props.auth.user.rol === 'TECNICO',
                                    'bg-emerald-100 text-emerald-700': $page.props.auth.user.rol === 'INVITADO'
                                }" class="px-2 py-0.5 rounded-full font-medium text-[10px]">
                                    {{ $page.props.auth.user.rol }}
                                </span>
                            </div>
                        </div>

                        <!-- Dropdown de cierre de sesión -->
                        <div class="relative">
                            <Link :href="route('logout')" method="post" as="button" 
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Salir
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Contenido Principal -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Alerta Flash de mensajes -->
                <div v-if="$page.props.flash?.message" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium">{{ $page.props.flash.message }}</span>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
*/


// ==============================================================================
// 2. DASHBOARD BENTO GRID ADAPTATIVO POR ROL (resources/js/Pages/Dashboard.vue)
// ==============================================================================
/*
<template>
    <AuthenticatedLayout>
        <Head title="Panel Principal" />

        <!-- Encabezado de Bienvenida -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-2xl font-black text-gray-900">¡Hola, {{ auth.user.name }}! 👋</h1>
                <p class="text-sm text-gray-500 mt-1">
                    <span v-if="auth.user.rol === 'ADMIN'">Panel de administración global y métricas del contrato.</span>
                    <span v-else-if="auth.user.rol === 'TECNICO'">Panel operativo para registro en campo y consulta de contratos asignados.</span>
                    <span v-else>Portal de transparencia de servicios ejecutados.</span>
                </p>
            </div>
            
            <!-- Selector de Contrato (Si aplica) -->
            <div v-if="contratos.length > 1" class="w-full md:w-64">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Contrato Activo</label>
                <select v-model="contratoSeleccionado" @change="cambiarContrato" class="w-full text-sm border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                    <option v-for="c in contratos" :key="c.id" :value="c.id">
                        {{ c.cliente?.nombre_cliente }} - {{ c.ubicacion_general }}
                    </option>
                </select>
            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- BENTO GRID PARA ROL ADMINISTRADOR                                   -->
        <!-- ==================================================================== -->
        <div v-if="auth.user.rol === 'ADMIN'" class="space-y-8">
            <!-- Bento Layout Principal para Administración -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                
                <!-- Bento 1: Registro Rápido (Destacado Grande 2 Cols) -->
                <div class="md:col-span-2 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-6 text-white shadow-lg flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition-transform"></div>
                    <div>
                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 text-xs font-semibold mb-3 backdrop-blur-md">
                            ⚡ Acción Rápida
                        </div>
                        <h2 class="text-xl font-bold">Gestión de Mantenimientos & Equipos</h2>
                        <p class="text-blue-100 text-xs mt-1 max-w-sm">Registra o consulta el historial completo de atenciones técnicas en sitio con validación por número de serie.</p>
                    </div>
                    <div class="mt-6 flex items-center space-x-3">
                        <Link :href="route('mantenimientos.create')" class="px-4 py-2 bg-white text-blue-700 font-bold rounded-xl text-xs hover:bg-blue-50 shadow-md transition">
                            + Nueva Atención
                        </Link>
                        <Link :href="route('mantenimientos.index')" class="px-4 py-2 bg-blue-800/60 text-white font-medium rounded-xl text-xs hover:bg-blue-800 border border-white/20 backdrop-blur-md transition">
                            Ver Historial
                        </Link>
                    </div>
                </div>

                <!-- Bento 2: Módulo Contratos -->
                <Link :href="route('admin.contratos.index')" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Gestión</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-bold text-gray-800 text-base">Contratos</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Metas, vigencias y asignación de técnicos.</p>
                    </div>
                </Link>

                <!-- Bento 3: Módulo Clientes & Sedes -->
                <Link :href="route('admin.clientes.index')" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Empresas</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-bold text-gray-800 text-base">Clientes & Sedes</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Empresas clientes y sucursales geolocalizadas.</p>
                    </div>
                </Link>

                <!-- Bento 4: Módulo Usuarios -->
                <Link :href="route('admin.users.index')" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between group">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Personal</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-bold text-gray-800 text-base">Usuarios & Roles</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Administradores, técnicos e invitados.</p>
                    </div>
                </Link>

                <!-- Bento 5: Exportación de Reportes Excel/CSV -->
                <a v-if="contratoActual" :href="route('admin.reportes.excel', contratoActual.id)" class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 hover:shadow-md transition flex flex-col justify-between group border-l-4 border-l-emerald-500">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span class="text-xs font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full">Excel / CSV</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="font-bold text-gray-800 text-base">Exportar Cierre</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Consolidado facturable del contrato activo.</p>
                    </div>
                </a>

                <!-- Bento 6: Avance Global del Contrato Activo -->
                <div class="md:col-span-2 bg-white p-5 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase text-gray-400">Progreso del Contrato</span>
                        <span class="text-lg font-black text-blue-600">{{ metricas.porcentaje_avance }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-700" :style="{ width: metricas.porcentaje_avance + '%' }"></div>
                    </div>
                    <div class="mt-3 flex justify-between text-xs text-gray-500">
                        <span>{{ metricas.total_mantenimientos }} mantenimientos completados</span>
                        <span>Meta: {{ metricas.meta_mantenimientos }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- BENTO GRID PARA ROL TÉCNICO                                         -->
        <!-- ==================================================================== -->
        <div v-else-if="auth.user.rol === 'TECNICO'" class="space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                
                <!-- Bento CTA Principal: Nueva Atención en Sitio -->
                <div class="md:col-span-2 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-3xl p-6 text-white shadow-lg flex flex-col justify-between relative overflow-hidden">
                    <div>
                        <span class="px-3 py-1 rounded-full bg-white/20 text-xs font-bold mb-3 inline-block backdrop-blur-md">
                            🛠️ Trabajo de Campo
                        </span>
                        <h2 class="text-2xl font-black">Registrar Mantenimiento en Sitio</h2>
                        <p class="text-emerald-100 text-xs mt-1 max-w-md">Ingresa la serie del equipo para autocompletar sus datos, aplicar la lista de cotejo y generar el reporte firmado.</p>
                    </div>
                    <div class="mt-6">
                        <Link :href="route('mantenimientos.create')" class="px-5 py-3 bg-white text-emerald-800 font-black rounded-xl text-sm shadow-md hover:bg-emerald-50 transition inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Formulario Único de Atención
                        </Link>
                    </div>
                </div>

                <!-- Bento: Mis Contratos Asignados -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">Mis Asignaciones</h3>
                        <p class="text-2xl font-black text-gray-900 mt-1">{{ contratos.length }} <span class="text-xs font-normal text-gray-500">contratos activos</span></p>
                    </div>
                    <div class="mt-4 text-xs text-blue-600 font-semibold">
                        Filtrado por tu usuario técnico
                    </div>
                </div>

                <!-- Bento: Mis Servicios Realizados -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="w-10 h-10 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">Servicios Ejecutados</h3>
                        <p class="text-2xl font-black text-gray-900 mt-1">{{ metricas.total_mantenimientos }}</p>
                    </div>
                    <Link :href="route('mantenimientos.index')" class="mt-4 text-xs text-indigo-600 font-semibold hover:underline">
                        Ver mis atenciones ➔
                    </Link>
                </div>

            </div>
        </div>

        <!-- ==================================================================== -->
        <!-- TABLA DE ACTIVIDAD RECIENTE (COMÚN PARA AMBOS ROLES)               -->
        <!-- ==================================================================== -->
        <div class="mt-8 bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800 text-base">Últimas Atenciones Registradas</h3>
                <Link :href="route('mantenimientos.index')" class="text-xs text-blue-600 font-semibold hover:underline">Ver todo</Link>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-gray-400 border-b border-gray-100 font-semibold">
                            <th class="pb-3">Fecha</th>
                            <th class="pb-3">Serie / Inventario</th>
                            <th class="pb-3">Equipo</th>
                            <th class="pb-3">Técnico</th>
                            <th class="pb-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="m in metricas.ultimos_mantenimientos" :key="m.id" class="hover:bg-gray-50 transition">
                            <td class="py-3 text-gray-600">{{ m.fecha }}</td>
                            <td class="py-3 font-bold text-gray-800">{{ m.serie }} <span v-if="m.codigo_inventario" class="text-gray-400 font-normal">({{ m.codigo_inventario }})</span></td>
                            <td class="py-3 text-gray-600">{{ m.marca }} {{ m.modelo }}</td>
                            <td class="py-3 text-gray-600 font-medium">{{ m.tecnico_nombre }}</td>
                            <td class="py-3 text-right">
                                <!-- Botón editar solo visible si es ADMIN o si el TÉCNICO es el dueño del registro -->
                                <Link v-if="auth.user.rol === 'ADMIN' || m.tecnico_id === auth.user.id" 
                                      :href="route('mantenimientos.edit', m.id)" 
                                      class="text-blue-600 hover:text-blue-800 font-bold">
                                    Editar
                                </Link>
                                <span v-else class="text-gray-300">Solo lectura</span>
                            </td>
                        </tr>
                        <tr v-if="!metricas.ultimos_mantenimientos?.length">
                            <td colspan="5" class="py-6 text-center text-gray-400">No hay registros recientes para este contrato.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    auth: Object,
    contratos: Array,
    contratoActual: Object,
    metricas: Object
});

const contratoSeleccionado = ref(props.contratoActual?.id || null);

const cambiarContrato = () => {
    router.get(route('dashboard'), { contrato_id: contratoSeleccionado.value }, { preserveState: true });
};
</script>
*/


// ==============================================================================
// 3. MIDDLEWARE DE SEGURIDAD POR ROLES (app/Http/Middleware/EnsureUserHasRole.php)
// ==============================================================================
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! in_array($request->user()->rol, $roles)) {
            return redirect()->route('dashboard')->with('flash.message', 'No tienes permisos de acceso a ese módulo.');
        }

        return $next($request);
    }
}


// ==============================================================================
// 4. CONTROLADOR DE MANTENIMIENTOS CON RESTRICCIÓN DE PROPIEDAD
// ==============================================================================
namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MantenimientoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Mantenimiento::with(['equipo', 'tecnico', 'contrato.cliente']);

        // Si es TÉCNICO, solo ve los mantenimientos de los contratos asignados a él
        if ($user->rol === 'TECNICO') {
            $contratoIds = $user->contratos()->pluck('contratos.id');
            $query->whereIn('contrato_id', $contratoIds);
        }

        $mantenimientos = $query->orderBy('fecha_mantenimiento', 'desc')->paginate(15);

        return Inertia::render('Mantenimientos/Index', [
            'mantenimientos' => $mantenimientos
        ]);
    }

    public function edit(Mantenimiento $mantenimiento)
    {
        $user = Auth::user();

        // REGLA DE NEGOCIO: Un técnico SOLO puede editar sus propios mantenimientos
        if ($user->rol === 'TECNICO' && $mantenimiento->tecnico_id !== $user->id) {
            return redirect()->route('mantenimientos.index')
                ->with('flash.message', 'Solo puedes editar los mantenimientos que tú mismo registraste.');
        }

        $mantenimiento->load(['equipo', 'contrato']);

        return Inertia::render('Mantenimientos/Edit', [
            'mantenimiento' => $mantenimiento
        ]);
    }
}
