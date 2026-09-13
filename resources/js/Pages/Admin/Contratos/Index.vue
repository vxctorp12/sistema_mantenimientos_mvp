<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

// Props recibidas del Backend
const props = defineProps({
  contratos: Object,
  clientes: Array,
  tecnicos: Array,
  listaTecnicos: Array,
  filters: Object
});

const listTecnicos = computed(() => props.listaTecnicos || props.tecnicos || []);

// Filtros Reactivos
const filters = reactive({
  search: props.filters?.search || '',
  estado: props.filters?.estado || ''
});

// Modales y Estados
const showModal = ref(false);
const showAssignModal = ref(false);
const isEditing = ref(false);
const currentContratoId = ref(null);

// Formulario de Contrato
const form = useForm({
  cliente_id: '',
  meta_equipos_total: 100,
  mantenimientos_por_equipo: 1,
  fecha_inicio: new Date().toISOString().substring(0, 10),
  fecha_limite: '',
  ubicacion_general: '',
  requerimientos_especiales: '',
  estado: 'ACTIVO'
});

// Formulario de Asignación de Técnicos
const assignForm = useForm({
  tecnicos_ids: []
});

// Aplicar Filtros con Inertia
const applyFilters = () => {
  router.get(route('admin.contratos.index'), filters, { preserveState: true, replace: true });
};

const resetFilters = () => {
  filters.search = '';
  filters.estado = '';
  applyFilters();
};

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(applyFilters, 300);
};

// Formato de Fecha
const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// Abrir Modal de Creación
const openCreateModal = () => {
  isEditing.value = false;
  currentContratoId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

// Abrir Modal de Edición
const openEditModal = (contrato) => {
  isEditing.value = true;
  currentContratoId.value = contrato.id;
  form.clearErrors();
  form.cliente_id = contrato.cliente_id;
  form.meta_equipos_total = contrato.meta_equipos_total;
  form.mantenimientos_por_equipo = contrato.mantenimientos_por_equipo;
  form.fecha_inicio = contrato.fecha_inicio ? contrato.fecha_inicio.substring(0, 10) : '';
  form.fecha_limite = contrato.fecha_limite ? contrato.fecha_limite.substring(0, 10) : '';
  form.ubicacion_general = contrato.ubicacion_general || '';
  form.requerimientos_especiales = contrato.requerimientos_especiales || '';
  form.estado = contrato.estado;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

// Guardar Contrato (Crear o Editar)
const saveContrato = () => {
  if (isEditing.value) {
    form.put(route('admin.contratos.update', currentContratoId.value), {
      onSuccess: () => closeModal()
    });
  } else {
    form.post(route('admin.contratos.store'), {
      onSuccess: () => closeModal()
    });
  }
};

// Abrir Modal de Asignación de Personal
const openAssignModal = (contrato) => {
  currentContratoId.value = contrato.id;
  assignForm.tecnicos_ids = contrato.tecnicos ? contrato.tecnicos.map(t => t.id) : [];
  showAssignModal.value = true;
};

// Guardar Asignación de Personal
const saveAssignments = () => {
  assignForm.post(route('admin.contratos.asignar-tecnicos', currentContratoId.value), {
    onSuccess: () => {
      showAssignModal.value = false;
    }
  });
};

// Cambiar Estado (Ocultar / Mostrar del Dashboard)
const toggleEstado = (contrato) => {
  const nuevoEstado = contrato.estado === 'ACTIVO' ? 'FINALIZADO' : 'ACTIVO';
  const accion = nuevoEstado === 'FINALIZADO' ? 'ocultar del Dashboard' : 'volver a mostrar en el Dashboard';
  
  if (confirm(`¿Deseas ${accion} este contrato?`)) {
    router.patch(route('admin.contratos.toggle-estado', contrato.id), {
      estado: nuevoEstado
    });
  }
};

// Exportar a Excel
const exportarExcel = (contratoId) => {
  window.open(route('admin.contratos.exportar-excel', contratoId), '_blank');
};
</script>

<template>
  <Head title="Gestión de Contratos" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white flex items-center gap-2">
            <span>Gestión de Contratos</span>
            <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
              {{ contratos?.total || 0 }} contratos
            </span>
          </h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            Administra los parámetros de servicio, asignación de técnicos y metas operativas.
          </p>
        </div>
        <button 
          @click="openCreateModal"
          class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg shadow transition-colors text-sm"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nuevo Contrato
        </button>
      </div>
    </template>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
      <!-- Barra de Filtros y Búsqueda -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Búsqueda General -->
          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Buscar por Cliente o Ubicación</label>
            <div class="relative">
              <input 
                v-model="filters.search"
                type="text" 
                placeholder="Nombre del cliente, ubicación..."
                class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-gray-700"
                @input="debounceSearch"
              />
              <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <!-- Filtro por Estado -->
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Filtrar por Estado</label>
            <select 
              v-model="filters.estado" 
              @change="applyFilters"
              class="w-full py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-gray-700"
            >
              <option value="">Todos los Estados</option>
              <option value="ACTIVO">Activos (Visibles en Dashboard)</option>
              <option value="FINALIZADO">Finalizados (Ocultos)</option>
              <option value="CANCELADO">Cancelados (Ocultos)</option>
            </select>
          </div>

          <!-- Acciones Rápidas -->
          <div class="flex items-end">
            <button 
              @click="resetFilters" 
              class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors"
            >
              Limpiar Filtros
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de Contratos -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
              <tr>
                <th class="p-4">Cliente / Ubicación</th>
                <th class="p-4 text-center">Meta Equipos</th>
                <th class="p-4 text-center">Frecuencia</th>
                <th class="p-4 text-center">Total Intervenciones</th>
                <th class="p-4">Vigencia</th>
                <th class="p-4">Técnicos Asignados</th>
                <th class="p-4 text-center">Estado Dashboard</th>
                <th class="p-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="contrato in contratos.data" :key="contrato.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <!-- Cliente -->
                <td class="p-4">
                  <div class="font-bold text-gray-900 dark:text-white">{{ contrato.cliente?.nombre_cliente || contrato.cliente?.nombre_empresa }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ contrato.ubicacion_general || 'Sede Principal' }}</div>
                </td>

                <!-- Meta Equipos -->
                <td class="p-4 text-center font-bold text-blue-600 dark:text-blue-400">
                  {{ contrato.meta_equipos_total || 'N/A' }}
                </td>

                <!-- Frecuencia -->
                <td class="p-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                    {{ contrato.mantenimientos_por_equipo }} mtto/equipo
                  </span>
                </td>

                <!-- Total Intervenciones -->
                <td class="p-4 text-center font-bold text-gray-900 dark:text-white">
                  {{ (contrato.meta_equipos_total || 0) * (contrato.mantenimientos_por_equipo || 1) }}
                </td>

                <!-- Vigencia -->
                <td class="p-4 text-xs">
                  <div><span class="font-semibold text-gray-500 dark:text-gray-400">Inicio:</span> {{ formatDate(contrato.fecha_inicio) }}</div>
                  <div><span class="font-semibold text-gray-500 dark:text-gray-400">Límite:</span> {{ contrato.fecha_limite ? formatDate(contrato.fecha_limite) : 'Indefinido' }}</div>
                </td>

                <!-- Técnicos Asignados -->
                <td class="p-4">
                  <div class="flex flex-wrap gap-1 max-w-xs">
                    <span 
                      v-for="tec in contrato.tecnicos" 
                      :key="tec.id"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-medium"
                    >
                      👤 {{ tec.name }}
                    </span>
                    <span v-if="!contrato.tecnicos?.length" class="text-xs text-amber-600 dark:text-amber-400 italic">
                      Sin técnicos asignados
                    </span>
                  </div>
                </td>

                <!-- Estado Dashboard -->
                <td class="p-4 text-center">
                  <span 
                    :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold',
                      contrato.estado === 'ACTIVO' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 
                      contrato.estado === 'FINALIZADO' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'
                    ]"
                  >
                    <span 
                      :class="[
                        'w-2 h-2 mr-1.5 rounded-full',
                        contrato.estado === 'ACTIVO' ? 'bg-emerald-500' : 
                        contrato.estado === 'FINALIZADO' ? 'bg-gray-400' : 'bg-rose-500'
                      ]"
                    ></span>
                    {{ contrato.estado }}
                  </span>
                </td>

                <!-- Acciones -->
                <td class="p-4 text-right">
                  <div class="flex items-center justify-end space-x-1.5">
                    <!-- Botón Asignar Personal -->
                    <button 
                      @click="openAssignModal(contrato)" 
                      title="Asignar Técnicos"
                      class="p-1.5 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </button>

                    <!-- Botón Editar -->
                    <button 
                      @click="openEditModal(contrato)" 
                      title="Editar Contrato"
                      class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>

                    <!-- Botón Ocultar/Activar del Dashboard -->
                    <button 
                      @click="toggleEstado(contrato)" 
                      :title="contrato.estado === 'ACTIVO' ? 'Ocultar del Dashboard' : 'Mostrar en Dashboard'"
                      :class="[
                        'p-1.5 rounded-lg transition-colors',
                        contrato.estado === 'ACTIVO' ? 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30'
                      ]"
                    >
                      <svg v-if="contrato.estado === 'ACTIVO'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.025 10.025 0 013.682-.863c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21f-3-3m-15-15L3 3" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>

                    <!-- Botón Exportar Excel -->
                    <button 
                      @click="exportarExcel(contrato.id)"
                      title="Exportar Excel"
                      class="p-1.5 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!contratos?.data?.length">
                <td colspan="8" class="p-8 text-center text-gray-500 dark:text-gray-400">
                  No se encontraron contratos registrados con los filtros seleccionados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="contratos?.links" :from="contratos?.from" :to="contratos?.to" :total="contratos?.total" />
      </div>

      <!-- MODAL 1: Crear / Editar Contrato -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6 border border-gray-100 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">
            {{ isEditing ? 'Editar Contrato' : 'Nuevo Contrato de Mantenimiento' }}
          </h2>

          <form @submit.prevent="saveContrato" class="space-y-4">
            <!-- Selección de Cliente -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cliente *</label>
              <select 
                v-model="form.cliente_id" 
                required
                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
              >
                <option value="" disabled>Seleccione un cliente...</option>
                <option v-for="c in clientes" :key="c.id" :value="c.id">
                  {{ c.nombre_cliente || c.nombre_empresa }}
                </option>
              </select>
            </div>

            <!-- Metas de Equipos y Frecuencia -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Meta Equipos Total *</label>
                <input 
                  v-model.number="form.meta_equipos_total" 
                  type="number" 
                  min="1"
                  required
                  placeholder="Ej. 100"
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Mantenimientos por Equipo *</label>
                <input 
                  v-model.number="form.mantenimientos_por_equipo" 
                  type="number" 
                  min="1" 
                  required
                  placeholder="Ej. 1"
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <!-- Fechas del Contrato -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha de Inicio *</label>
                <input 
                  v-model="form.fecha_inicio" 
                  type="date" 
                  required
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha Límite (Opcional)</label>
                <input 
                  v-model="form.fecha_limite" 
                  type="date" 
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <!-- Ubicación y Estado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Ubicación General</label>
                <input 
                  v-model="form.ubicacion_general" 
                  type="text" 
                  placeholder="Ej. Sede Central, Edificio BCR..."
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Estado del Contrato</label>
                <select 
                  v-model="form.estado" 
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                  <option value="ACTIVO">ACTIVO (Mostrar en Dashboard)</option>
                  <option value="FINALIZADO">FINALIZADO (Ocultar)</option>
                  <option value="CANCELADO">CANCELADO (Ocultar)</option>
                </select>
              </div>
            </div>

            <!-- Requerimientos Especiales -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Requerimientos Especiales</label>
              <textarea 
                v-model="form.requerimientos_especiales" 
                rows="2"
                placeholder="Instrucciones de seguridad, horarios autorizados..."
                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <!-- Botones de Acción -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
              <button 
                type="button" 
                @click="closeModal" 
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg text-sm font-semibold shadow transition-colors"
              >
                {{ isEditing ? 'Guardar Cambios' : 'Crear Contrato' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- MODAL 2: Asignación Múltiple de Técnicos -->
      <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 border border-gray-100 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Asignar Personal Técnico</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            Selecciona los técnicos que estarán autorizados para atender este contrato.
          </p>

          <form @submit.prevent="saveAssignments" class="space-y-4">
            <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg p-2 bg-gray-50 dark:bg-gray-700/50">
              <label 
                v-for="tec in listTecnicos" 
                :key="tec.id"
                class="flex items-center justify-between p-2 hover:bg-white dark:hover:bg-gray-700 rounded cursor-pointer transition-colors"
              >
                <div class="flex items-center space-x-3">
                  <input 
                    type="checkbox" 
                    :value="tec.id" 
                    v-model="assignForm.tecnicos_ids"
                    class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-blue-500"
                  />
                  <span class="text-sm font-medium text-gray-800 dark:text-white">{{ tec.name }}</span>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-400">{{ tec.email }}</span>
              </label>

              <div v-if="!listTecnicos.length" class="p-4 text-center text-xs text-gray-400">
                No hay técnicos registrados.
              </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
              <button 
                type="button" 
                @click="showAssignModal = false" 
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                :disabled="assignForm.processing"
                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg text-sm font-semibold shadow transition-colors"
              >
                Guardar Asignaciones
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
