<script setup>
import { ref, watch } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    equipos: Object,
    filters: Object,
    pendientesCount: Number,
    totalCount: Number,
});

const search = ref(props.filters?.search || '');
const tipoEquipo = ref(props.filters?.tipo_equipo || '');
const sinMantenimiento = ref(props.filters?.sin_mantenimiento || '');

const aplicarFiltros = () => {
    router.get(route('equipos.index'), {
        search: search.value,
        tipo_equipo: tipoEquipo.value,
        sin_mantenimiento: sinMantenimiento.value,
    }, { preserveState: true, replace: true });
};

watch([search, tipoEquipo, sinMantenimiento], () => {
    aplicarFiltros();
});
</script>

<template>
    <Head title="Inventario de Equipos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
                        <span>Inventario de Equipos</span>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                            {{ equipos?.total || 0 }} equipos
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Catálogo general de hardware e historial de atenciones
                    </p>
                </div>
                <div>
                    <Link :href="route('mantenimientos.create')" 
                          class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm rounded-lg font-medium hover:bg-blue-700 dark:hover:bg-blue-600 transition flex items-center gap-2">
                        <span>+</span> Registrar Atención
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-3 gap-4">
                <input v-model="search" type="text" placeholder="Buscar por Serie, Inventario, Marca, Modelo, Usuario..." 
                       class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
                
                <select v-model="tipoEquipo" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:ring-blue-500">
                    <option value="">Todos los tipos</option>
                    <option value="DESKTOP">Desktop</option>
                    <option value="LAPTOP">Laptop</option>
                    <option value="IMPRESORA">Impresora</option>
                    <option value="OTRO">Otro</option>
                </select>

                <select v-model="sinMantenimiento" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:ring-blue-500">
                    <option value="">Todos los estados (Con/Sin Mantenimiento)</option>
                    <option value="1">⚠️ Pendientes de Mantenimiento (0 atenciones)</option>
                </select>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-4">Número de Serie</th>
                            <th class="p-4">Código Inventario</th>
                            <th class="p-4">Tipo / Marca / Modelo</th>
                            <th class="p-4">Usuario Responsable</th>
                            <th class="p-4">Departamento / Unidad</th>
                            <th class="p-4 text-center">Atenciones</th>
                            <th class="p-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                        <tr v-for="equipo in equipos.data" :key="equipo.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="p-4 font-mono font-bold text-gray-900 dark:text-white">
                                {{ equipo.numero_serie }}
                            </td>
                            <td class="p-4 text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ equipo.codigo_inventario || 'Sin Código' }}
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 mr-2">
                                    {{ equipo.tipo_equipo }}
                                </span>
                                {{ equipo.marca }} {{ equipo.modelo }}
                            </td>
                            <td class="p-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ equipo.usuario_asignado || 'N/A' }}
                            </td>
                            <td class="p-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ equipo.departamento_unidad || 'N/A' }}
                            </td>
                            <td class="p-4 text-center">
                                <span v-if="equipo.mantenimientos_count > 0" class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300">
                                    ✓ {{ equipo.mantenimientos_count }} atenciones
                                </span>
                                <span v-else class="px-3 py-1 text-xs font-bold rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                    ⚠️ Pendiente (0)
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <Link :href="route('mantenimientos.create', { serie: equipo.numero_serie })" 
                                      class="inline-flex items-center text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-900/30 px-3 py-1.5 rounded-lg transition">
                                    + Atender
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!equipos?.data?.length">
                            <td colspan="7" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                No se encontraron equipos registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <Pagination :links="equipos?.links" :from="equipos?.from" :to="equipos?.to" :total="equipos?.total" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
