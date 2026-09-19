<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    mantenimientos: Object,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const userRole = computed(() => currentUser.value?.rol || 'TECNICO');

const search = ref(props.filters?.search || '');
const tipoEquipo = ref(props.filters?.tipo_equipo || '');
const impreso = ref(props.filters?.impreso ?? '');
const fechaInicio = ref(props.filters?.fecha_inicio || '');
const fechaFin = ref(props.filters?.fecha_fin || '');

const aplicarFiltros = () => {
    router.get(route('mantenimientos.index'), {
        search: search.value,
        tipo_equipo: tipoEquipo.value,
        impreso: impreso.value,
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value,
    }, { preserveState: true, replace: true });
};

const limpiarFiltros = () => {
    search.value = '';
    tipoEquipo.value = '';
    impreso.value = '';
    fechaInicio.value = '';
    fechaFin.value = '';
    aplicarFiltros();
};

const exportarPdfMasivoUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (tipoEquipo.value) params.append('tipo_equipo', tipoEquipo.value);
    if (impreso.value !== '' && impreso.value !== null) params.append('impreso', impreso.value);
    if (fechaInicio.value) params.append('fecha_inicio', fechaInicio.value);
    if (fechaFin.value) params.append('fecha_fin', fechaFin.value);
    return `${route('mantenimientos.exportar-pdf-masivo')}?${params.toString()}`;
});

watch([search, tipoEquipo, impreso, fechaInicio, fechaFin], () => {
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
                    <Link :href="route('equipos.index')" 
                          class="px-3.5 py-2 bg-gray-200 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 text-xs font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-700 transition">
                        Ver Inventario
                    </Link>
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
                            <option value="IMPRESORA">Impresora</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>

                    <!-- Fecha Desde -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Fecha Desde</label>
                        <input v-model="fechaInicio" type="date" 
                               class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500" />
                    </div>

                    <!-- Fecha Hasta -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 dark:text-zinc-400 mb-1">Fecha Hasta</label>
                        <input v-model="fechaFin" type="date" 
                               class="w-full border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg text-xs py-2 px-3 focus:ring-blue-500" />
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

                    <button v-if="search || tipoEquipo || impreso !== '' || fechaInicio || fechaFin" 
                            @click="limpiarFiltros" 
                            type="button" 
                            class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold">
                        ✕ Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-zinc-800">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 dark:bg-zinc-800/60 text-gray-600 dark:text-zinc-300 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-4">Fecha</th>
                            <th class="p-4">Serie / Inv.</th>
                            <th class="p-4">Equipo</th>
                            <th class="p-4">Usuario / Unidad</th>
                            <th class="p-4">Técnico</th>
                            <th class="p-4 text-center">Impresión</th>
                            <th class="p-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800 text-sm text-gray-700 dark:text-zinc-300">
                        <tr v-for="item in mantenimientos.data" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/40">
                            <td class="p-4 text-xs font-medium text-gray-500 dark:text-zinc-400">
                                {{ item.fecha_mantenimiento ? new Date(item.fecha_mantenimiento).toLocaleDateString('es-ES') : 'N/A' }}
                            </td>
                            <td class="p-4">
                                <Link :href="route('mantenimientos.show', item.id)" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ item.equipo?.numero_serie }}
                                </Link>
                                <div class="text-xs text-gray-400 dark:text-zinc-500">{{ item.equipo?.codigo_inventario || 'Sin Inv.' }}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 mr-2">
                                    {{ item.equipo?.tipo_equipo }}
                                </span>
                                {{ item.equipo?.marca }} {{ item.equipo?.modelo }}
                            </td>
                            <td class="p-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.equipo?.usuario_asignado || 'N/A' }}</div>
                                <div class="text-xs text-gray-400 dark:text-zinc-500">{{ item.equipo?.departamento_unidad || 'N/A' }}</div>
                            </td>
                            <td class="p-4 text-xs font-medium text-gray-600 dark:text-zinc-300">
                                {{ item.tecnico?.name || item.tecnico?.nombre || 'Técnico' }}
                            </td>
                            <td class="p-4 text-center">
                                <span v-if="item.impreso" class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                    ✓ Impreso
                                </span>
                                <span v-else class="px-3 py-1 text-xs font-bold rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                    ⏳ Pendiente
                                </span>
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
                                <span v-else class="text-xs text-gray-400 dark:text-zinc-500">Solo lectura</span>
                            </td>
                        </tr>
                        <tr v-if="!mantenimientos?.data?.length">
                            <td colspan="7" class="p-8 text-center text-gray-400 dark:text-zinc-500">
                                No se encontraron registros de mantenimiento con los filtros seleccionados.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <Pagination :links="mantenimientos?.links" :from="mantenimientos?.from" :to="mantenimientos?.to" :total="mantenimientos?.total" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
