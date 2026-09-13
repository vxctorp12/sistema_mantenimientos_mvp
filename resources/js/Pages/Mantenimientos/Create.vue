<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
    contratoActivo: Object,
    serieInicial: String,
});

const form = useForm({
    contrato_id: props.contratoActivo?.id || 1,
    numero_serie: props.serieInicial || '',
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

onMounted(() => {
    if (props.serieInicial) {
        buscarEquipo();
    }
});

const guardar = () => {
    form.post(route('mantenimientos.store'));
};
</script>

<template>
    <Head title="Nuevo Mantenimiento en Sitio" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                        Formulario Único de Atención
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Registro de mantenimiento preventivo en sitio
                    </p>
                </div>
                <Link :href="route('mantenimientos.index')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                    ← Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <form @submit.prevent="guardar" class="space-y-6">
                    <!-- Sección 1: Búsqueda por Serie -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800">
                        <label class="block text-xs font-bold text-blue-800 dark:text-blue-300 uppercase mb-1">Número de Serie (Búsqueda en Caliente)</label>
                        <div class="flex gap-2">
                            <input v-model="form.numero_serie" @blur="buscarEquipo" type="text" placeholder="Ingresa o escanea la serie..."
                                   class="flex-1 border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-blue-500 font-mono text-lg font-bold" required />
                        </div>
                        <span v-if="mensajeBusqueda" class="text-xs font-medium text-blue-700 dark:text-blue-300 mt-2 block">
                            {{ mensajeBusqueda }}
                        </span>
                    </div>

                    <!-- Sección 2: Datos del Equipo -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Código Inventario</label>
                            <input v-model="form.codigo_inventario" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Tipo de Equipo</label>
                            <select v-model="form.tipo_equipo" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm">
                                <option value="DESKTOP">Desktop</option>
                                <option value="LAPTOP">Laptop</option>
                                <option value="IMPRESORA">Impresora</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Marca</label>
                            <input v-model="form.marca" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Modelo</label>
                            <input v-model="form.modelo" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Unidad / Depto.</label>
                            <input v-model="form.departamento_unidad" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Usuario Responsable</label>
                            <input v-model="form.usuario_asignado" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>
                    </div>

                    <!-- Sección 3: Checklist -->
                    <div>
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase mb-3">Lista de Verificación</h3>
                        <div class="space-y-3 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                            <div v-for="(item, idx) in form.checklists" :key="idx" class="flex flex-col md:flex-row md:items-center justify-between gap-2 p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                                <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" v-model="item.hecho" class="rounded text-blue-600 focus:ring-blue-500 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600" />
                                    {{ item.item }}
                                </label>
                                <input v-model="item.nota" type="text" placeholder="Comentarios u observaciones..." class="text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded px-2 py-1 w-full md:w-64" />
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Observaciones del Servicio</label>
                        <textarea v-model="form.observaciones" rows="3" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" placeholder="Detalle adicional del trabajo realizado..."></textarea>
                    </div>

                    <!-- Botón de Guardar -->
                    <div class="flex justify-end gap-3">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-blue-600 dark:bg-blue-500 text-white rounded-lg font-bold hover:bg-blue-700 dark:hover:bg-blue-600 transition disabled:opacity-50">
                            Guardar Mantenimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
