<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    auth: Object,
    contratos: Array,
    contratoSeleccionado: Object,
    contratoActual: Object,
    metricas: Object
});

const page = usePage();
const currentUser = computed(() => props.auth?.user || page.props.auth?.user || {});
const userRole = computed(() => currentUser.value?.rol || 'TECNICO');

const contratoActivo = computed(() => props.contratoActual || props.contratoSeleccionado);
const contratoId = ref(contratoActivo.value?.id || '');

watch(contratoId, (nuevoId) => {
    if (nuevoId) {
        router.get(route('dashboard'), { contrato_id: nuevoId }, { preserveState: true });
    }
});

const seleccionarContrato = (id) => {
    contratoId.value = id;
    router.get(route('dashboard'), { contrato_id: id }, { preserveState: true });
};

const volverAContratos = () => {
    contratoId.value = '';
    router.get(route('dashboard'), {}, { preserveState: true });
};

const cambiarContrato = () => {
    if (contratoId.value) {
        router.get(route('dashboard'), { contrato_id: contratoId.value }, { preserveState: true });
    }
};

const exportarExcel = () => {
    if (contratoActivo.value?.id) {
        window.open(route('admin.contratos.exportar-excel', contratoActivo.value.id), '_blank');
    }
};
</script>

<template>
    <Head title="Panel Principal - Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                        Dashboard RILAZ - Mantenimientos
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Cliente: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ contratoActivo?.cliente_nombre || 'General' }}</span> 
                        | Ubicación: <span class="font-medium text-gray-600 dark:text-gray-300">{{ contratoActivo?.ubicacion || 'Sede Principal' }}</span>
                    </p>
                </div>
                
                <div v-if="contratos?.length > 1 && (userRole === 'ADMIN' || contratoActivo)" class="flex items-center space-x-3">
                    <label for="contrato" class="text-xs font-semibold text-gray-600 dark:text-gray-300 whitespace-nowrap">Contrato Activo:</label>
                    <select 
                        id="contrato" 
                        v-model="contratoId" 
                        @change="cambiarContrato"
                        class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 font-medium"
                    >
                        <option v-for="c in contratos" :key="c.id" :value="c.id">
                            {{ c.nombre_cliente || c.cliente?.nombre_cliente || c.cliente?.nombre_empresa }} ({{ c.ubicacion_general || 'Sede Principal' }})
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Encabezado de Bienvenida -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white">¡Hola, {{ currentUser.name }}! 👋</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <span v-if="userRole === 'ADMIN'">Panel de administración global y métricas del contrato.</span>
                        <span v-else-if="userRole === 'TECNICO'">Panel operativo para registro en campo y consulta de contratos asignados.</span>
                        <span v-else>Portal de transparencia de servicios ejecutados.</span>
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span :class="{
                        'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300': userRole === 'ADMIN',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300': userRole === 'TECNICO',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': userRole === 'INVITADO'
                    }" class="px-3 py-1 rounded-full font-bold text-xs">
                        Rol: {{ userRole }}
                    </span>
                </div>
            </div>

            <!-- ==================================================================== -->
            <!-- BENTO GRID PARA ROL TÉCNICO                                         -->
            <!-- ==================================================================== -->
            <div v-if="userRole === 'TECNICO'" class="space-y-6">
                <!-- CASO 1: TÉCNICO SIN CONTRATOS ASIGNADOS -->
                <div v-if="!contratos || contratos.length === 0" class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                    <div class="w-14 h-14 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sin Contratos Asignados</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                        Actualmente no estás asignado a ningún contrato activo. Contacta a un administrador para que te asigne a un servicio de mantenimiento y puedas comenzar a registrar atenciones en sitio.
                    </p>
                </div>

                <!-- CASO 2: TÉCNICO CON MÁS DE 1 CONTRATO Y NINGUNO SELECCIONADO AÚN -->
                <div v-else-if="contratos.length > 1 && !contratoActivo" class="space-y-6">
                    <div class="text-center space-y-1">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tus Contratos Asignados ({{ contratos.length }})</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Selecciona el contrato en el cual deseas trabajar para ingresar atenciones o consultar el progreso.</p>
                    </div>

                    <div class="flex flex-wrap justify-center gap-6">
                        <div 
                            v-for="c in contratos" 
                            :key="c.id"
                            @click="seleccionarContrato(c.id)"
                            class="w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] max-w-md bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md hover:border-blue-500 dark:hover:border-blue-500 transition-all cursor-pointer flex flex-col justify-between group"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                        {{ c.estado || 'ACTIVO' }}
                                    </span>
                                    <span class="text-xs text-blue-600 dark:text-blue-400 font-bold group-hover:translate-x-1 transition-transform">
                                        Ingresar al Panel ➔
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-base group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ c.nombre_cliente || c.cliente?.nombre_cliente || c.cliente?.nombre_empresa }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    📍 {{ c.ubicacion_general || 'Sede Principal' }}
                                </p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-center text-xs mb-1.5">
                                    <span class="font-semibold text-gray-500 dark:text-gray-400">Progreso del Servicio</span>
                                    <span class="font-black text-blue-600 dark:text-blue-400">{{ c.porcentaje_avance || 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: Math.min(c.porcentaje_avance || 0, 100) + '%' }"></div>
                                </div>
                                <div class="mt-2 text-[11px] text-gray-400 dark:text-gray-400 flex justify-between">
                                    <span>{{ c.total_realizados || 0 }} mantenimientos</span>
                                    <span>Meta: {{ c.meta_mantenimientos_total || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASO 3: TÉCNICO CON 1 SOLO CONTRATO O CON UN CONTRATO YA SELECCIONADO -->
                <div v-else class="space-y-6">
                    <div v-if="contratos.length > 1" class="flex items-center justify-between">
                        <button 
                            @click="volverAContratos"
                            class="inline-flex items-center text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            ← Volver a Mis Contratos Asignados
                        </button>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Viendo servicio: <strong class="text-gray-800 dark:text-white">{{ contratoActivo?.cliente_nombre }}</strong></span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                        <!-- Bento CTA Principal: Registrar Mantenimiento en Sitio -->
                        <div class="lg:col-span-3 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-3xl p-6 text-white shadow-lg flex flex-col justify-between relative overflow-hidden">
                            <div>
                                <span class="px-3 py-1 rounded-full bg-white/20 text-xs font-bold mb-3 inline-block backdrop-blur-md">
                                    🛠️ Trabajo de Campo
                                </span>
                                <h2 class="text-2xl font-black">Registrar Mantenimiento en Sitio</h2>
                                <p class="text-emerald-100 text-xs mt-1 max-w-md">
                                    {{ contratoActivo?.cliente_nombre ? `Registra atenciones técnicas para ${contratoActivo.cliente_nombre}.` : 'Ingresa la serie del equipo para autocompletar sus datos y registrar la atención.' }}
                                </p>
                            </div>
                            <div class="mt-6">
                                <Link :href="route('mantenimientos.create', { contrato_id: contratoActivo?.id })" class="px-5 py-3 bg-white text-emerald-800 font-black rounded-xl text-sm shadow-md hover:bg-emerald-50 transition inline-flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Formulario Único de Atención
                                </Link>
                            </div>
                        </div>

                        <!-- Bento Ampliado: Servicios Ejecutados -->
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800 dark:text-white text-base">Servicios Ejecutados</h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Progreso operativo del contrato</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                        Meta: {{ metricas?.meta_equipos || 0 }} equipos
                                    </span>
                                </div>

                                <!-- Mantenimientos Realizados por el Técnico Actual -->
                                <div class="mt-4 p-3 bg-blue-50/70 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 rounded-2xl flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg">🛠️</span>
                                        <div>
                                            <p class="text-xs font-bold text-blue-950 dark:text-blue-200">
                                                Has realizado <span class="text-sm font-black text-blue-600 dark:text-blue-400">{{ metricas?.mis_realizados ?? 0 }}</span> mantenimientos
                                            </p>
                                            <p class="text-[11px] text-blue-600/80 dark:text-blue-300/70">Atenciones registradas por tu usuario</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Avance Global del Contrato -->
                                <div class="mt-4">
                                    <div class="flex items-baseline justify-between text-xs mb-1">
                                        <span class="text-gray-500 dark:text-gray-400 font-semibold">Total global del contrato</span>
                                        <span class="font-bold text-gray-900 dark:text-white">
                                            <strong class="text-base text-blue-600 dark:text-blue-400">{{ metricas?.total_realizados || 0 }}</strong> / {{ metricas?.meta_mantenimientos_total || 0 }} mtto ({{ metricas?.porcentaje_avance_global || 0 }}%)
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: Math.min(metricas?.porcentaje_avance_global || 0, 100) + '%' }"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                                <span class="text-gray-400">Atenciones del contrato</span>
                                <Link :href="route('mantenimientos.index')" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                    Ver historial ➔
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================================================================== -->
            <!-- BENTO GRID PARA ROL ADMINISTRADOR                                   -->
            <!-- ==================================================================== -->
            <div v-else-if="userRole === 'ADMIN'" class="space-y-6">
                <!-- Si no hay métricas disponibles -->
                <div v-if="!metricas" class="bg-white dark:bg-gray-800 p-12 text-center rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 text-gray-500 dark:text-gray-400">
                    <p class="text-lg font-semibold mb-2">No hay contratos activos registrados en el sistema.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Ejecuta la siembra de datos o crea un contrato para visualizar las métricas.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    
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
                    <Link :href="route('admin.contratos.index')" class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-purple-600 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/40 px-2 py-0.5 rounded-full">Gestión</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="font-bold text-gray-800 dark:text-white text-base">Contratos</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Metas, vigencias y asignación de técnicos.</p>
                        </div>
                    </Link>

                    <!-- Bento 3: Módulo Clientes & Sedes -->
                    <Link :href="route('admin.clientes.index')" class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-900/30 rounded-2xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/40 px-2 py-0.5 rounded-full">Empresas</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="font-bold text-gray-800 dark:text-white text-base">Clientes & Sedes</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Empresas clientes y sucursales geolocalizadas.</p>
                        </div>
                    </Link>

                    <!-- Bento 4: Módulo Usuarios -->
                    <Link :href="route('admin.usuarios.index')" class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition flex flex-col justify-between group">
                        <div class="flex items-center justify-between">
                            <div class="w-10 h-10 bg-amber-50 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-amber-600 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/40 px-2 py-0.5 rounded-full">Personal</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="font-bold text-gray-800 dark:text-white text-base">Usuarios & Roles</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Administradores, técnicos e invitados.</p>
                        </div>
                    </Link>

                    <!-- Bento 5: Exportación de Reportes Excel/CSV -->
                    <button v-if="contratoActivo" @click="exportarExcel" class="bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition flex flex-col justify-between group text-left border-l-4 border-l-emerald-500">
                        <div class="flex items-center justify-between">
                            <div class="w-10 h-10 bg-teal-50 dark:bg-teal-900/30 rounded-2xl flex items-center justify-center text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <span class="text-xs font-bold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-900/40 px-2 py-0.5 rounded-full">Excel / CSV</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="font-bold text-gray-800 dark:text-white text-base">Exportar Cierre</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Consolidado facturable del contrato activo.</p>
                        </div>
                    </button>

                    <!-- Bento 6: Avance Global del Contrato Activo -->
                    <div class="md:col-span-2 bg-white dark:bg-gray-800 p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase text-gray-400 dark:text-gray-400">Progreso del Contrato</span>
                            <span class="text-lg font-black text-blue-600 dark:text-blue-400">{{ metricas.porcentaje_avance_global || metricas.porcentaje_avance }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-700" :style="{ width: Math.min(metricas.porcentaje_avance_global || metricas.porcentaje_avance, 100) + '%' }"></div>
                        </div>
                        <div class="mt-3 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ metricas.total_realizados || metricas.total_mantenimientos }} mantenimientos completados</span>
                            <span>Meta: {{ metricas.meta_mantenimientos_total || metricas.meta_mantenimientos }}</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ==================================================================== -->
            <!-- BENTO GRID PARA ROL INVITADO (CLIENTE)                              -->
            <!-- ==================================================================== -->
            <div v-else class="space-y-6">
                <div v-if="metricas" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Avance del Contrato</h3>
                        <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ metricas.porcentaje_avance_global || metricas.porcentaje_avance }}%</div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ metricas.total_realizados }} de {{ metricas.meta_mantenimientos_total }} mantenimientos ejecutados</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipos Atendidos</h3>
                        <div class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ metricas.equipos_unicos_atendidos }}</div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">de un total de {{ metricas.meta_equipos }} equipos en contrato</p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Comprobantes Firmados</h3>
                        <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">{{ metricas.total_impresos }}</div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Atenciones registradas con firma</p>
                    </div>
                </div>
            </div>

            <!-- ==================================================================== -->
            <!-- TABLA DE ACTIVIDAD RECIENTE (SOLO SI HAY CONTRATO SELECCIONADO)      -->
            <!-- ==================================================================== -->
            <div v-if="metricas?.ultimos_mantenimientos" class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800 dark:text-white text-base">Últimas Atenciones Registradas</h3>
                    <Link :href="route('mantenimientos.index')" class="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline">Ver todo ➔</Link>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead>
                            <tr class="text-gray-400 border-b border-gray-100 dark:border-gray-700 font-semibold">
                                <th class="pb-3">Fecha</th>
                                <th class="pb-3">Serie / Inventario</th>
                                <th class="pb-3">Equipo</th>
                                <th class="pb-3">Técnico</th>
                                <th class="pb-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-for="m in metricas.ultimos_mantenimientos" :key="m.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ m.fecha }}</td>
                                <td class="py-3 font-bold text-gray-800 dark:text-white">
                                    {{ m.serie || m.equipo_serie }} 
                                    <span v-if="m.codigo_inventario || (m.equipo_inventario && m.equipo_inventario !== 'N/A')" class="text-gray-400 font-normal">
                                        ({{ m.codigo_inventario || m.equipo_inventario }})
                                    </span>
                                </td>
                                <td class="py-3 text-gray-600 dark:text-gray-300">{{ m.marca_modelo || ((m.marca || '') + ' ' + (m.modelo || '')) }}</td>
                                <td class="py-3 text-gray-600 dark:text-gray-300 font-medium">{{ m.tecnico_nombre }}</td>
                                <td class="py-3 text-right">
                                    <!-- Botón editar solo visible si es ADMIN o si el TÉCNICO es el dueño del registro -->
                                    <Link v-if="userRole === 'ADMIN' || m.tecnico_id === currentUser.id" 
                                          :href="route('mantenimientos.edit', m.id)" 
                                          class="text-blue-600 dark:text-blue-400 hover:text-blue-800 font-bold">
                                        Editar
                                    </Link>
                                    <span v-else class="text-gray-400 dark:text-gray-500">Solo lectura</span>
                                </td>
                            </tr>
                            <tr v-if="!metricas.ultimos_mantenimientos?.length">
                                <td colspan="5" class="py-6 text-center text-gray-400 dark:text-gray-500">No hay registros recientes para este contrato.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
