<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    contrato: Object
});
</script>

<template>
    <Head title="Detalles del Contrato" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
                        <Link :href="route('dashboard', { contrato_id: contrato.id })" class="text-blue-500 hover:underline">Dashboard</Link>
                        <span class="text-gray-400">/</span>
                        <span>Detalles del Contrato</span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Información general y ubicaciones registradas
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- TARJETA DE INFORMACIÓN GENERAL -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Información del Cliente y Contrato
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cliente</p>
                        <p class="font-bold text-gray-900 dark:text-white text-base">{{ contrato.cliente_nombre }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nombre de Contacto</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ contrato.contacto_nombre || 'No registrado' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Vigencia</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ contrato.fecha_inicio }} - {{ contrato.fecha_limite }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Ubicación General</p>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">{{ contrato.ubicacion || 'No registrada' }}</p>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Requerimientos Especiales</p>
                    <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                        {{ contrato.requerimientos_especiales || 'No hay requerimientos especiales registrados para este contrato.' }}
                    </div>
                </div>
            </div>

            <!-- TARJETA DE SEDES -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Sedes e Instalaciones ({{ contrato.sedes?.length || 0 }})
                </h3>

                <div v-if="!contrato.sedes || contrato.sedes.length === 0" class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No hay información registrada sobre sedes para este cliente.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="sede in contrato.sedes" :key="sede.id" class="border border-gray-100 dark:border-gray-700 rounded-2xl p-5 hover:shadow-md transition bg-gray-50 dark:bg-gray-700/20">
                        <h4 class="font-bold text-gray-900 dark:text-white text-base mb-1">{{ sede.nombre_sede }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 h-10 line-clamp-2">{{ sede.direccion || 'Dirección no registrada' }}</p>
                        
                        <div class="flex items-center gap-3 mt-auto pt-4 border-t border-gray-200 dark:border-gray-600">
                            <a v-if="sede.latitud && sede.longitud" 
                               :href="`https://www.google.com/maps/search/?api=1&query=${sede.latitud},${sede.longitud}`" 
                               target="_blank" 
                               class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-800/60 rounded-xl text-xs font-bold transition">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Google Maps
                            </a>
                            <div v-else class="flex-1 text-center py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 italic bg-gray-200/50 dark:bg-gray-800/50 rounded-xl">
                                Sin Coordenadas
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
