<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    mantenimientos: Object,
    tecnicos: Array,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const userRole = computed(() => currentUser.value?.rol || 'TECNICO');

const search = ref(props.filters?.search || '');
const tipoEquipo = ref(props.filters?.tipo_equipo || '');
const impreso = ref(props.filters?.impreso ?? '');
const tipoFiltroFecha = ref(props.filters?.tipo_filtro_fecha || 'rango');
const fechaExacta = ref(props.filters?.fecha_exacta || '');
const fechaInicio = ref(props.filters?.fecha_inicio || '');
const fechaFin = ref(props.filters?.fecha_fin || '');
const contratoId = ref(props.filters?.contrato_id || '');
const tecnicoId = ref(props.filters?.tecnico_id || '');

const aplicarFiltros = () => {
    let fInicio = fechaInicio.value;
    let fFin = fechaFin.value;
    if (tipoFiltroFecha.value === 'simple') {
        fInicio = fechaExacta.value;
        fFin = fechaExacta.value;
    }

    router.get(route('mantenimientos.index'), {
        search: search.value,
        tipo_equipo: tipoEquipo.value,
        impreso: impreso.value,
        tipo_filtro_fecha: tipoFiltroFecha.value,
        fecha_exacta: fechaExacta.value,
        fecha_inicio: fInicio,
        fecha_fin: fFin,
        contrato_id: contratoId.value,
        tecnico_id: tecnicoId.value,
    }, { preserveState: true, replace: true });
};

const limpiarFiltros = () => {
    search.value = '';
    tipoEquipo.value = '';
    impreso.value = '';
    tipoFiltroFecha.value = 'rango';
    fechaExacta.value = '';
    fechaInicio.value = '';
    fechaFin.value = '';
    contratoId.value = '';
    tecnicoId.value = '';
    aplicarFiltros();
};

const exportarPdfMasivoUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (tipoEquipo.value) params.append('tipo_equipo', tipoEquipo.value);
    if (impreso.value !== '' && impreso.value !== null) params.append('impreso', impreso.value);
    if (tipoFiltroFecha.value) params.append('tipo_filtro_fecha', tipoFiltroFecha.value);
    if (fechaExacta.value) params.append('fecha_exacta', fechaExacta.value);
    
    let fInicio = fechaInicio.value;
    let fFin = fechaFin.value;
    if (tipoFiltroFecha.value === 'simple') {
        fInicio = fechaExacta.value;
        fFin = fechaExacta.value;
    }

    if (fInicio) params.append('fecha_inicio', fInicio);
    if (fFin) params.append('fecha_fin', fFin);
    if (contratoId.value) params.append('contrato_id', contratoId.value);
    if (tecnicoId.value) params.append('tecnico_id', tecnicoId.value);
    return `${route('mantenimientos.exportar-pdf-masivo')}?${params.toString()}`;
});

const mostrarDato = (valor) => {
    if (!valor) return '';
    const v = String(valor).toUpperCase().trim();
    if (['N/A', 'S/N', 'SIN SERIE', 'NO APLICA', 'POR DEFINIR'].includes(v)) return '';
    return valor;
};

const eliminarMantenimiento = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar este mantenimiento? Si el equipo asociado no tiene otros mantenimientos, también será eliminado del inventario. Esta acción no se puede deshacer.')) {
        router.delete(route('mantenimientos.destroy', id), {
            preserveScroll: true,
        });
    }
};

const toggleImpreso = (item) => {
    // Optimistic toggle in UI
    const originalValue = item.impreso;
    item.impreso = !item.impreso;

    router.put(route('mantenimientos.toggle-impreso', item.id), { impreso: item.impreso }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            // Revert on error
            item.impreso = originalValue;
        }
    });
};

watch([search, tipoEquipo, impreso, tipoFiltroFecha, fechaExacta, fechaInicio, fechaFin, contratoId, tecnicoId], () => {
    aplicarFiltros();
});
</script>

<template>
    <Head title="Registro de Mantenimientos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
                        <span>{{ userRole === 'TECNICO' ? 'Mis Mantenimientos Registrados' : 'Registro de Mantenimientos' }}</span>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                            {{ mantenimientos?.total || 0 }} registros
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ userRole === 'TECNICO' ? 'Historial de atenciones realizadas por tu usuario' : 'Historial general de intervenciones técnicas en campo' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2.5 items-center">
                    <a :href="exportarPdfMasivoUrl" target="_blank"
                       class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-1.5">
                        <span>🖨️</span> Imprimir PDF Filtrados ({{ mantenimientos?.total || 0 }})
                    </a>

                    <Link :href="route('mantenimientos.create')" 
                          class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-xs rounded-lg font-bold hover:bg-blue-700 dark:hover:bg-blue-600 transition flex items-center gap-1">
                        <span>+</span> Nuevo Mantenimiento
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Filtros Avanzados (con Fechas) -->
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    <!-- Búsqueda -->
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Búsqueda General</label>
                        <input v-model="search" type="text" placeholder="Serie, Inventario, Usuario, Marca..." 
                               class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500 focus:border-blue-500" />
                    </div>

                    <!-- Tipo Equipo -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Tipo de Equipo</label>
                        <select v-model="tipoEquipo" class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500">
                            <option value="">Todos los tipos</option>
                            <option value="DESKTOP">Desktop</option>
                            <option value="LAPTOP">Laptop</option>
                            <option value="ESCANER">Escáner</option>
                            <option value="IMPRESORA">Impresora</option>
                        </select>
                    </div>

                    <!-- Selector de Fechas (Simple o Rango) -->
                    <div class="md:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Filtro de Fechas</label>
                            <select v-model="tipoFiltroFecha" class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500">
                                <option value="simple">Fecha simple</option>
                                <option value="rango">Rango de fechas</option>
                            </select>
                        </div>

                        <template v-if="tipoFiltroFecha === 'simple'">
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Fecha</label>
                                <input v-model="fechaExacta" type="date" 
                                       class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500" />
                            </div>
                        </template>

                        <template v-else>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Fecha Desde</label>
                                <input v-model="fechaInicio" type="date" 
                                       class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Fecha Hasta</label>
                                <input v-model="fechaFin" type="date" 
                                       class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500" />
                            </div>
                        </template>
                    </div>

                    <!-- Técnico (Visible si hay técnicos disponibles) -->
                    <div v-if="tecnicos && tecnicos.length > 0">
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Técnico</label>
                        <select v-model="tecnicoId" class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500">
                            <option value="">Todos los técnicos</option>
                            <option v-for="tecnico in tecnicos" :key="tecnico.id" :value="tecnico.id">
                                {{ tecnico.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-zinc-800 text-xs">
                    <div class="flex items-center gap-3">
                        <label class="font-semibold text-gray-600 dark:text-zinc-400">Estado de Impresión:</label>
                        <select v-model="impreso" class="border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-1 px-2.5 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="1">Impresos (PDF)</option>
                            <option value="0">Pendientes de impresión</option>
                        </select>
                    </div>

                    <button v-if="search || tipoEquipo || impreso !== '' || fechaExacta || fechaInicio || fechaFin || tecnicoId" 
                            @click="limpiarFiltros" 
                            type="button" 
                            class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold">
                        ✕ Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-max">
                        <thead class="bg-gray-100 dark:bg-zinc-800/60 text-gray-600 dark:text-zinc-300 text-xs font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="p-4">Fecha</th>
                                <th class="p-4">Serie / Inv.</th>
                                <th class="p-4">Equipo</th>
                                <th class="p-4 hidden md:table-cell">Usuario / Unidad</th>
                                <th class="p-4 hidden sm:table-cell">Técnico</th>
                                <th class="p-4 text-center">Impresión</th>
                                <th class="p-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800 text-sm text-gray-700 dark:text-zinc-300">
                        <tr v-for="item in mantenimientos.data" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/40">
                            <td class="p-4 text-xs font-medium text-gray-500 dark:text-zinc-400">
                                {{ item.fecha_mantenimiento ? String(item.fecha_mantenimiento).substring(0, 10).split('-').reverse().join('/') : 'N/A' }}
                            </td>
                            <td class="p-4">
                                <Link :href="route('mantenimientos.show', item.id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline block">
                                    {{ mostrarDato(item.equipo?.codigo_inventario) || 'Sin Activo Fijo' }}
                                </Link>
                                <div v-if="mostrarDato(item.equipo?.numero_serie)" class="text-xs text-gray-400 dark:text-zinc-500">
                                    {{ mostrarDato(item.equipo?.numero_serie) }}
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 mr-2">
                                    {{ item.equipo?.tipo_equipo }}
                                </span>
                                <template v-if="item.contrato?.form_version !== 'v2'">
                                    {{ mostrarDato(item.equipo?.marca) }} 
                                    {{ mostrarDato(item.equipo?.modelo) }}
                                </template>
                            </td>
                            <td class="p-4 hidden md:table-cell">
                                <div class="font-medium text-gray-900 dark:text-white">{{ mostrarDato(item.equipo?.usuario_asignado) }}</div>
                                <div class="text-xs text-gray-400 dark:text-zinc-500">{{ mostrarDato(item.equipo?.departamento_unidad) }}</div>
                            </td>
                            <td class="p-4 text-xs font-medium text-gray-600 dark:text-zinc-300 hidden sm:table-cell">
                                {{ item.tecnico?.name || item.tecnico?.nombre || 'Técnico' }}
                            </td>
                            <td class="p-4 text-center">
                                <label class="inline-flex items-center cursor-pointer" :title="item.impreso ? 'Desmarcar impreso' : 'Marcar impreso'">
                                    <input type="checkbox" :checked="item.impreso" @change.prevent="toggleImpreso(item)" 
                                           class="w-5 h-5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 bg-gray-50 dark:bg-zinc-800 dark:border-zinc-600 dark:checked:bg-emerald-500 transition-colors cursor-pointer" />
                                </label>
                            </td>
                            <td class="p-4 text-right flex items-center justify-end gap-3">
                                <a :href="route('mantenimientos.pdf', item.id)" target="_blank"
                                   title="Generar / Imprimir PDF Individual"
                                   class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
                                    🖨️ PDF
                                </a>
                                <Link v-if="userRole === 'ADMIN' || item.tecnico_id === currentUser.id" 
                                      :href="route('mantenimientos.edit', item.id)" 
                                      class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    Editar
                                </Link>
                                <button v-if="userRole === 'ADMIN'" 
                                      @click="eliminarMantenimiento(item.id)" 
                                      class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline">
                                    Eliminar
                                </button>
                                <span v-if="userRole !== 'ADMIN' && item.tecnico_id !== currentUser.id" class="text-xs text-gray-400 dark:text-zinc-500">Solo lectura</span>
                            </td>
                        </tr>
                        <tr v-if="!mantenimientos?.data?.length">
                            <td colspan="7" class="p-8 text-center text-gray-400 dark:text-zinc-500">
                                No se encontraron registros de mantenimiento con los filtros seleccionados.
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>

                <!-- Paginación -->
                <Pagination :links="mantenimientos?.links" :from="mantenimientos?.from" :to="mantenimientos?.to" :total="mantenimientos?.total" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
