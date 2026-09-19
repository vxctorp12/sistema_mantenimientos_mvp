<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  BarElement,
  PointElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js';
import { Line, Bar, Pie } from 'vue-chartjs';

ChartJS.register(
  Title, Tooltip, Legend, LineElement, BarElement, PointElement, CategoryScale, LinearScale, ArcElement
);

const props = defineProps({
    contrato: Object,
    mantenimientosDiarios: Array,
    mantenimientosPorTecnico: Array,
    mantenimientosPorTipo: Array,
    mantenimientosPorMarca: Array,
    mantenimientos: Object,
    filters: Object,
});

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.rol || 'TECNICO');

const search = ref(props.filters?.search || '');

const aplicarFiltros = () => {
    router.get(route('contratos.dashboard', props.contrato.id), {
        search: search.value,
    }, { preserveState: true, replace: true });
};

// Datos para gráficos
const chartDataDiarios = computed(() => {
    return {
        labels: props.mantenimientosDiarios.map(d => d.fecha),
        datasets: [{
            label: 'Mantenimientos por día',
            data: props.mantenimientosDiarios.map(d => d.total),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.2)',
            fill: true,
            tension: 0.4
        }]
    };
});

const chartDataTecnicos = computed(() => {
    return {
        labels: props.mantenimientosPorTecnico.map(d => d.tecnico),
        datasets: [{
            data: props.mantenimientosPorTecnico.map(d => d.total),
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6b7280'],
        }]
    };
});

const chartDataTipo = computed(() => {
    return {
        labels: props.mantenimientosPorTipo.map(d => d.tipo),
        datasets: [{
            label: 'Por Tipo',
            data: props.mantenimientosPorTipo.map(d => d.total),
            backgroundColor: '#3b82f6',
        }]
    };
});

const chartDataMarca = computed(() => {
    return {
        labels: props.mantenimientosPorMarca.map(d => d.marca),
        datasets: [{
            label: 'Por Marca',
            data: props.mantenimientosPorMarca.map(d => d.total),
            backgroundColor: '#10b981',
        }]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' }
    }
};

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
};
</script>

<template>
    <Head title="Dashboard del Contrato" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
                        <Link :href="route('dashboard')" class="text-blue-500 hover:underline">Dashboard</Link>
                        <span class="text-gray-400">/</span>
                        <span>Métricas del Contrato</span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Cliente: <strong>{{ contrato.cliente_nombre }}</strong> | Vigencia: {{ contrato.fecha_inicio }} - {{ contrato.fecha_limite }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Fila superior: Líneas (Diario) y Pastel (Técnicos) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Gráfico Diario -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 h-96 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Mantenimientos Diarios</h3>
                    <div class="flex-1 relative">
                        <Line :data="chartDataDiarios" :options="chartOptions" />
                    </div>
                </div>

                <!-- Gráfico Técnicos -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 h-96 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 text-center">Por Técnico</h3>
                    <div class="flex-1 relative">
                        <Pie :data="chartDataTecnicos" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- Fila media: Barras (Tipo) y Barras (Marca) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Gráfico Tipos -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 h-80 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Por Tipo de Equipo</h3>
                    <div class="flex-1 relative">
                        <Bar :data="chartDataTipo" :options="barOptions" />
                    </div>
                </div>

                <!-- Gráfico Marcas -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 h-80 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Por Marca</h3>
                    <div class="flex-1 relative">
                        <Bar :data="chartDataMarca" :options="barOptions" />
                    </div>
                </div>
            </div>

            <!-- Tabla de Mantenimientos -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Lista de Mantenimientos</h3>
                    <div class="w-full sm:w-1/3 flex gap-2">
                        <input v-model="search" @keyup.enter="aplicarFiltros" type="text" placeholder="Buscar por Serie/Activo..." class="w-full border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        <button @click="aplicarFiltros" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">Buscar</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="p-4">Fecha</th>
                                <th class="p-4">Equipo</th>
                                <th class="p-4">Serie / Inventario</th>
                                <th class="p-4">Técnico</th>
                                <th class="p-4">Firma</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                            <tr v-for="mtto in mantenimientos.data" :key="mtto.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="p-4">
                                    {{ mtto.fecha_mantenimiento ? new Date(mtto.fecha_mantenimiento).toLocaleDateString() : 'N/A' }}
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ mtto.equipo?.tipo_equipo || 'OTRO' }}</div>
                                    <div class="text-xs text-gray-500">{{ mtto.equipo?.marca }} {{ mtto.equipo?.modelo }}</div>
                                </td>
                                <td class="p-4 text-xs font-mono">
                                    <div>Serie: {{ mtto.equipo?.numero_serie || 'N/A' }}</div>
                                    <div class="text-gray-500">Inv: {{ mtto.equipo?.codigo_inventario || 'N/A' }}</div>
                                </td>
                                <td class="p-4">
                                    {{ mtto.tecnico?.name || 'Sin Asignar' }}
                                </td>
                                <td class="p-4">
                                    <span v-if="mtto.firma_cliente" class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        Firmado
                                    </span>
                                    <span v-else class="px-2 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Pendiente
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <Link v-if="userRole !== 'INVITADO'" :href="route('mantenimientos.edit', mtto.id)" class="inline-block px-3 py-1.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-medium rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                                        Editar
                                    </Link>
                                    <span v-else class="text-gray-400 dark:text-gray-500">Solo lectura</span>
                                </td>
                            </tr>
                            <tr v-if="!mantenimientos?.data?.length">
                                <td colspan="6" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                    No hay mantenimientos registrados aún.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <Pagination :links="mantenimientos?.links" :from="mantenimientos?.from" :to="mantenimientos?.to" :total="mantenimientos?.total" />
            </div>
            
        </div>
    </AuthenticatedLayout>
</template>
