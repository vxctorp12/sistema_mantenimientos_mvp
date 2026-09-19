<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 bg-slate-900 dark:bg-slate-950 text-slate-100 relative overflow-hidden select-none">
        <!-- Elementos de luz ambiental de fondo -->
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-sky-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Encabezado de Marca y Logo -->
        <div class="mb-6 text-center flex flex-col items-center z-10">
            <Link href="/" class="inline-flex items-center justify-center p-4 rounded-2xl bg-slate-800/80 border border-slate-700/80 shadow-2xl backdrop-blur-xl hover:border-blue-500/50 hover:scale-105 transition-all duration-300 group">
                <ApplicationLogo variant="dark" class="h-14 w-auto object-contain" />
            </Link>
            <h1 class="mt-4 text-2xl font-black tracking-tight text-white">
                RILAZ Mantenimientos
            </h1>
            <p class="text-xs text-slate-400 font-medium mt-1">
                Portal Operativo de Registro en Sitio
            </p>
        </div>

        <!-- Contenedor Principal con efecto Glassmorphism -->
        <div class="w-full sm:max-w-md bg-slate-800/90 border border-slate-700/80 shadow-2xl rounded-3xl p-6 sm:p-8 backdrop-blur-xl z-10 transition-all duration-300">
            <slot />
        </div>

        <!-- Pie de página -->
        <div class="mt-8 text-center text-xs text-slate-500 z-10">
            &copy; {{ new Date().getFullYear() }} RILAZ S.A. de C.V. &bull; Todos los derechos reservados
        </div>
    </div>
</template>
