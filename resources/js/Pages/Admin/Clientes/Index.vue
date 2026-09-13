<script setup>
import { ref, watch } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    clientes: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showClienteModal = ref(false);
const showSedeModal = ref(false);
const isEditingCliente = ref(false);
const editingClienteId = ref(null);
const selectedClienteForSede = ref(null);

const clienteForm = useForm({
    nombre_cliente: '',
    contacto_nombre: '',
    contacto_email: '',
    telefono: '',
});

const sedeForm = useForm({
    nombre_sede: '',
    direccion: '',
    telefono: '',
    latitud: '',
    longitud: '',
});

const aplicarFiltros = () => {
    router.get(route('admin.clientes.index'), {
        search: search.value,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    aplicarFiltros();
});

const abrirCrearCliente = () => {
    isEditingCliente.value = false;
    editingClienteId.value = null;
    clienteForm.reset();
    clienteForm.clearErrors();
    showClienteModal.value = true;
};

const abrirEditarCliente = (cliente) => {
    isEditingCliente.value = true;
    editingClienteId.value = cliente.id;
    clienteForm.clearErrors();
    clienteForm.nombre_cliente = cliente.nombre_cliente;
    clienteForm.contacto_nombre = cliente.contacto_nombre || '';
    clienteForm.contacto_email = cliente.contacto_email || '';
    clienteForm.telefono = cliente.telefono || '';
    showClienteModal.value = true;
};

const guardarCliente = () => {
    if (isEditingCliente.value) {
        clienteForm.put(route('admin.clientes.update', editingClienteId.value), {
            onSuccess: () => { showClienteModal.value = false; clienteForm.reset(); }
        });
    } else {
        clienteForm.post(route('admin.clientes.store'), {
            onSuccess: () => { showClienteModal.value = false; clienteForm.reset(); }
        });
    }
};

const abrirAgregarSede = (cliente) => {
    selectedClienteForSede.value = cliente;
    sedeForm.reset();
    sedeForm.clearErrors();
    showSedeModal.value = true;
};

const guardarSede = () => {
    if (!selectedClienteForSede.value) return;
    sedeForm.post(route('admin.clientes.sedes.store', selectedClienteForSede.value.id), {
        onSuccess: () => { showSedeModal.value = false; sedeForm.reset(); }
    });
};
</script>

<template>
    <Head title="Gestión de Clientes y Sedes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
                        <span>Gestión de Clientes y Sedes</span>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                            {{ clientes?.total || 0 }} clientes
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Directorio de empresas, contactos y sucursales
                    </p>
                </div>
                <button @click="abrirCrearCliente" 
                        class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm rounded-lg font-medium hover:bg-blue-700 dark:hover:bg-blue-600 transition flex items-center gap-2">
                    <span>+</span> Nuevo Cliente
                </button>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <input v-model="search" type="text" placeholder="Buscar por Nombre de Empresa o Contacto..." 
                       class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-4">Empresa / Cliente</th>
                            <th class="p-4">Contacto Principal</th>
                            <th class="p-4 text-center">Sedes</th>
                            <th class="p-4 text-center">Contratos</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                        <tr v-for="c in clientes.data" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="p-4 font-bold text-gray-900 dark:text-white">
                                {{ c.nombre_cliente }}
                            </td>
                            <td class="p-4">
                                <div class="font-medium">{{ c.contacto_nombre || 'Sin Contacto' }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ c.contacto_email }} | {{ c.telefono }}</div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ c.sedes_count ?? 0 }} sedes
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    {{ c.contratos_count ?? 0 }} contratos
                                </span>
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <button @click="abrirAgregarSede(c)" class="px-2.5 py-1 text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded font-medium hover:bg-blue-100 transition">
                                    + Sede
                                </button>
                                <button @click="abrirEditarCliente(c)" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    Editar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!clientes?.data?.length">
                            <td colspan="5" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                No se encontraron clientes registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <Pagination :links="clientes?.links" :from="clientes?.from" :to="clientes?.to" :total="clientes?.total" />
            </div>

            <!-- Modal Cliente -->
            <div v-if="showClienteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        {{ isEditingCliente ? 'Editar Cliente' : 'Nuevo Cliente' }}
                    </h3>

                    <form @submit.prevent="guardarCliente" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre de la Empresa / Cliente</label>
                            <input v-model="clienteForm.nombre_cliente" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre del Contacto</label>
                            <input v-model="clienteForm.contacto_nombre" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Email del Contacto</label>
                            <input v-model="clienteForm.contacto_email" type="email" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Teléfono</label>
                            <input v-model="clienteForm.telefono" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showClienteModal = false" class="px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="clienteForm.processing" class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-xs font-bold rounded-lg hover:bg-blue-700 transition">
                                {{ isEditingCliente ? 'Guardar Cambios' : 'Registrar Cliente' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Sede -->
            <div v-if="showSedeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                        Nueva Sede para {{ selectedClienteForSede?.nombre_cliente }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Ingresa los datos de la sucursal u oficina</p>

                    <form @submit.prevent="guardarSede" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre de la Sede</label>
                            <input v-model="sedeForm.nombre_sede" type="text" placeholder="Ej: Sede Central, Sucursal Norte" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Dirección</label>
                            <input v-model="sedeForm.direccion" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Teléfono</label>
                            <input v-model="sedeForm.telefono" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showSedeModal = false" class="px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="sedeForm.processing" class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-xs font-bold rounded-lg hover:bg-blue-700 transition">
                                Guardar Sede
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
