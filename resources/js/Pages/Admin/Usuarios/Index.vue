<script setup>
import { ref, watch } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    usuarios: Object,
    clientes: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const rol = ref(props.filters?.rol || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    rol: 'TECNICO',
    cliente_id: '',
    activo: true,
});

const aplicarFiltros = () => {
    router.get(route('admin.usuarios.index'), {
        search: search.value,
        rol: rol.value,
    }, { preserveState: true, replace: true });
};

watch([search, rol], () => {
    aplicarFiltros();
});

const abrirCrear = () => {
    isEditing.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const abrirEditar = (user) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.username = user.username || '';
    form.email = user.email;
    form.password = '';
    form.rol = user.rol;
    form.cliente_id = user.cliente_id || '';
    form.activo = Boolean(user.activo);
    showModal.value = true;
};

const guardar = () => {
    if (isEditing.value) {
        form.put(route('admin.usuarios.update', editingUserId.value), {
            onSuccess: () => { showModal.value = false; form.reset(); }
        });
    } else {
        form.post(route('admin.usuarios.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); }
        });
    }
};

const toggleEstado = (user) => {
    router.patch(route('admin.usuarios.toggle-state', user.id));
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
                        <span>Gestión de Usuarios</span>
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                            {{ usuarios?.total || 0 }} usuarios
                        </span>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Administración de cuentas, asignación de roles y accesos
                    </p>
                </div>
                <button @click="abrirCrear" 
                        class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-sm rounded-lg font-medium hover:bg-blue-700 dark:hover:bg-blue-600 transition flex items-center gap-2">
                    <span>+</span> Crear Usuario
                </button>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-4">
                <input v-model="search" type="text" placeholder="Buscar por Nombre, Usuario o Email..." 
                       class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500" />
                
                <select v-model="rol" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm focus:ring-blue-500">
                    <option value="">Todos los Roles</option>
                    <option value="ADMIN">Administrador</option>
                    <option value="TECNICO">Técnico</option>
                    <option value="INVITADO">Cliente (Invitado)</option>
                </select>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="p-4">Usuario</th>
                            <th class="p-4">Nombre de Usuario</th>
                            <th class="p-4">Rol</th>
                            <th class="p-4">Empresa / Cliente</th>
                            <th class="p-4 text-center">Estado</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                        <tr v-for="user in usuarios.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="p-4">
                                <div class="font-bold text-gray-900 dark:text-white">{{ user.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                            </td>
                            <td class="p-4 text-xs font-medium text-gray-600 dark:text-gray-300">
                                <span v-if="user.username" class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono text-gray-800 dark:text-gray-200">
                                    @{{ user.username }}
                                </span>
                                <span v-else class="text-gray-400 dark:text-gray-500 italic">Sin usuario</span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full" 
                                      :class="{
                                          'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300': user.rol === 'ADMIN',
                                          'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': user.rol === 'TECNICO',
                                          'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': user.rol === 'INVITADO'
                                      }">
                                    {{ user.rol }}
                                </span>
                            </td>
                            <td class="p-4 text-xs font-medium">
                                {{ user.cliente?.nombre_cliente || 'N/A' }}
                            </td>
                            <td class="p-4 text-center">
                                <span v-if="user.activo" class="px-2.5 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    ✓ Activo
                                </span>
                                <span v-else class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                    Inactivo
                                </span>
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <button @click="abrirEditar(user)" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                                    Editar
                                </button>
                                <button @click="toggleEstado(user)" 
                                        :class="user.activo ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 hover:bg-amber-200' : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 hover:bg-green-200'"
                                        class="px-2.5 py-1 text-xs rounded font-medium transition">
                                    {{ user.activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!usuarios?.data?.length">
                            <td colspan="6" class="p-8 text-center text-gray-400 dark:text-gray-500">
                                No se encontraron usuarios.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <Pagination :links="usuarios?.links" :from="usuarios?.from" :to="usuarios?.to" :total="usuarios?.total" />
            </div>

            <!-- Modal Formulario -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h3>

                    <form @submit.prevent="guardar" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre Completo *</label>
                            <input v-model="form.name" type="text" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required />
                            <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre de Usuario (Username)</label>
                            <input v-model="form.username" type="text" placeholder="Ej. jdoe" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" />
                            <span v-if="form.errors.username" class="text-xs text-red-500">{{ form.errors.username }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Correo Electrónico</label>
                            <input v-model="form.email" type="email" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required />
                            <span v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                Contraseña {{ isEditing ? '(Dejar vacía para mantener actual)' : '' }}
                            </label>
                            <input v-model="form.password" type="password" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" :required="!isEditing" />
                            <span v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Rol</label>
                            <select v-model="form.rol" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm">
                                <option value="ADMIN">Administrador</option>
                                <option value="TECNICO">Técnico</option>
                                <option value="INVITADO">Cliente (Invitado)</option>
                            </select>
                        </div>

                        <div v-if="form.rol === 'INVITADO'">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Empresa / Cliente Asociado</label>
                            <select v-model="form.cliente_id" class="w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg text-sm" required>
                                <option value="">Selecciona Cliente</option>
                                <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_cliente }}</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" v-model="form.activo" id="activoCheck" class="rounded text-blue-600 dark:bg-gray-700 dark:border-gray-600" />
                            <label for="activoCheck" class="text-xs font-medium text-gray-700 dark:text-gray-300">Usuario Activo</label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 dark:bg-blue-500 text-white text-xs font-bold rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition">
                                {{ isEditing ? 'Guardar Cambios' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
