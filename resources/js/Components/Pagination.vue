<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
    from: Number,
    to: Number,
    total: Number,
});
</script>

<template>
    <div v-if="links && links.length > 3" class="px-4 py-3 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="text-gray-500 dark:text-gray-400">
            Mostrando <span class="font-bold text-gray-800 dark:text-white">{{ from || 0 }}</span> a <span class="font-bold text-gray-800 dark:text-white">{{ to || 0 }}</span> de <span class="font-bold text-gray-800 dark:text-white">{{ total || 0 }}</span> registros
        </div>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" 
                     class="px-3 py-1.5 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-700 rounded-lg cursor-not-allowed select-none"
                     v-html="link.label">
                </div>
                <Link v-else 
                      :href="link.url" 
                      class="px-3 py-1.5 border rounded-lg font-medium transition-colors" 
                      :class="{
                          'bg-blue-600 text-white border-blue-600 dark:bg-blue-500 dark:border-blue-500': link.active,
                          'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600': !link.active
                      }" 
                      v-html="link.label" />
            </template>
        </div>
    </div>
</template>
