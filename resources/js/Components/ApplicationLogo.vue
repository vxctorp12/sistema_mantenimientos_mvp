<script setup>
defineProps({
    variant: {
        type: String,
        default: 'auto', // 'auto', 'light', 'dark'
    }
});
</script>

<template>
    <div class="inline-flex items-center">
        <!-- Logo para modo claro / fondos claros (Letras Oscuras) -->
        <img 
            v-if="variant === 'auto' || variant === 'light'"
            src="https://rilaz.com.sv/wp-content/uploads/2026/02/Logo2021-2.png" 
            alt="RILAZ Logo" 
            v-bind="$attrs"
            :class="[
                'h-14 w-auto max-w-full object-contain filter drop-shadow-md transition-all duration-300',
                variant === 'auto' ? 'block dark:hidden' : 'block'
            ]" 
        />

        <!-- Logo para modo oscuro / fondos oscuros (Letras Blancas) -->
        <img 
            v-if="variant === 'auto' || variant === 'dark'"
            src="https://rilaz.com.sv/wp-content/uploads/2026/03/logo-nuevo-40x49.png" 
            alt="RILAZ Logo" 
            v-bind="$attrs"
            :class="[
                'h-14 w-auto max-w-full object-contain filter drop-shadow-md transition-all duration-300',
                variant === 'auto' ? 'hidden dark:block' : 'block'
            ]" 
        />
    </div>
</template>
