<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    mantenimiento: Object,
});

const formImpresion = useForm({});

const marcarImpreso = () => {
    formImpresion.post(route('mantenimientos.marcar-impreso', props.mantenimiento.id));
};
</script>

<template>
    <Head :title="`Detalle Mantenimiento #${mantenimiento.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                        Detalle de Mantenimiento #{{ mantenimiento.id }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Fecha: {{ mantenimiento.fecha_mantenimiento ? String(mantenimiento.fecha_mantenimiento).substring(0, 10).split('-').reverse().join('/') : 'N/A' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('mantenimientos.index')" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        ← Volver al listado
                    </Link>
                    <button v-if="!mantenimiento.impreso" @click="marcarImpreso" :disabled="formImpresion.processing"
                            class="px-4 py-2 bg-green-600 dark:bg-green-500 text-white text-sm rounded-lg font-bold hover:bg-green-700 dark:hover:bg-green-600 transition">
                        ✓ Marcar como Impreso
                    </button>
                    <span v-else class="px-3 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs font-bold rounded-full">
                        ✓ Impreso el {{ new Date(mantenimiento.fecha_impresion).toLocaleDateString('es-ES') }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
                <!-- Información del Equipo -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3">Equipo Intervenido</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="block text-xs text-gray-400 dark:text-gray-500">Número de Serie</span>
                            <strong class="font-mono text-gray-900 dark:text-white">{{ mantenimiento.equipo?.numero_serie }}</strong>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 dark:text-gray-500">Código Inventario</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ mantenimiento.equipo?.codigo_inventario || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 dark:text-gray-500">Tipo / Marca / Modelo</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                {{ mantenimiento.equipo?.tipo_equipo }} - {{ mantenimiento.equipo?.marca }} {{ mantenimiento.equipo?.modelo }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 dark:text-gray-500">Usuario Responsable</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ mantenimiento.equipo?.usuario_asignado || 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Lista de Verificación -->
                <div v-if="mantenimiento.checklists?.length">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3">Lista de Verificación (Checklist)</h3>
                    <div class="space-y-2">
                        <div v-for="chk in mantenimiento.checklists" :key="chk.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded border border-gray-200 dark:border-gray-700 text-sm">
                            <div class="flex items-center gap-2">
                                <span :class="chk.realizado ? 'text-green-600 dark:text-green-400 font-bold' : 'text-gray-400'">
                                    {{ chk.realizado ? '✓' : '✗' }}
                                </span>
                                <span class="text-gray-800 dark:text-gray-200 font-medium">{{ chk.item_verificacion }}</span>
                            </div>
                            <span v-if="chk.comentarios" class="text-xs text-gray-500 dark:text-gray-400 italic">{{ chk.comentarios }}</span>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <div v-if="mantenimiento.observaciones">
                    <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Observaciones</h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-3 rounded border border-gray-200 dark:border-gray-700">{{ mantenimiento.observaciones }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
