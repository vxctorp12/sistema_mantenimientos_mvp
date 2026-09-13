<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    mantenimiento: Object
});

const form = useForm({
    observaciones: props.mantenimiento.observaciones || '',
    recomendaciones: props.mantenimiento.recomendaciones || '',
    estado_firma: props.mantenimiento.estado_firma || 'PENDIENTE',
    checklists: props.mantenimiento.checklists ? props.mantenimiento.checklists.map(c => ({
        id: c.id,
        item: c.item_verificacion,
        hecho: Boolean(c.realizado),
        nota: c.comentarios || ''
    })) : []
});

const guardar = () => {
    form.put(route('mantenimientos.update', props.mantenimiento.id));
};
</script>

<template>
    <Head title="Editar Mantenimiento" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                        Editar Atención Técnica #{{ mantenimiento.id }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Equipo: {{ mantenimiento.equipo?.marca }} {{ mantenimiento.equipo?.modelo }} (S/N: {{ mantenimiento.equipo?.numero_serie }})
                    </p>
                </div>
                <Link :href="route('mantenimientos.index')" 
                      class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    ← Volver al Historial
                </Link>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-6">
                <!-- Info resumen del equipo -->
                <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Cliente</span>
                        <div class="font-bold text-gray-900 dark:text-white">{{ mantenimiento.contrato?.cliente?.nombre_cliente || 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-500 dark:text-gray-400">N° Serie</span>
                        <div class="font-bold text-gray-900 dark:text-white">{{ mantenimiento.equipo?.numero_serie }}</div>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Inventario</span>
                        <div class="font-bold text-gray-900 dark:text-white">{{ mantenimiento.equipo?.codigo_inventario || 'S/I' }}</div>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Tipo de Equipo</span>
                        <div class="font-bold text-gray-900 dark:text-white">{{ mantenimiento.equipo?.tipo_equipo }}</div>
                    </div>
                </div>

                <form @submit.prevent="guardar" class="space-y-6">
                    <!-- Observaciones -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Observaciones de la Atención
                        </label>
                        <textarea v-model="form.observaciones" 
                                  rows="3" 
                                  placeholder="Detalle el estado general del equipo y hallazgos..."
                                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Recomendaciones -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Recomendaciones Técnicas
                        </label>
                        <textarea v-model="form.recomendaciones" 
                                  rows="3" 
                                  placeholder="Sugerencias de mantenimiento preventivo/correctivo futuro..."
                                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Checklist -->
                    <div v-if="form.checklists.length">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Lista de Cotejo / Checklist de Verificación
                        </label>
                        <div class="space-y-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-700/50 divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="(chk, idx) in form.checklists" :key="idx" class="pt-2 first:pt-0 flex flex-col md:flex-row md:items-center justify-between gap-2">
                                <label class="flex items-center space-x-2 text-xs font-medium text-gray-800 dark:text-gray-200 cursor-pointer">
                                    <input type="checkbox" v-model="chk.hecho" class="rounded text-blue-600 dark:bg-gray-700 dark:border-gray-600 focus:ring-blue-500" />
                                    <span>{{ chk.item }}</span>
                                </label>
                                <input v-model="chk.nota" type="text" placeholder="Nota adicional..." class="w-full md:w-64 text-xs py-1 px-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-gray-900 dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Estado de Firma -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Estado de Firma / Documento
                        </label>
                        <select v-model="form.estado_firma" class="w-full md:w-64 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="PENDIENTE">PENDIENTE (Borrador / Sin Firma)</option>
                            <option value="FIRMADO_FISICO">FIRMADO_FISICO (Documento Conformado)</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <Link :href="route('mantenimientos.index')" 
                              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium">
                            Cancelar
                        </Link>
                        <button type="submit" 
                                :disabled="form.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg text-sm font-semibold shadow transition-colors">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
