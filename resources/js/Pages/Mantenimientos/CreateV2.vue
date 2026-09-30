<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
  contratos: Array,
  contratoActivo: Object,
  contrato_id_default: [Number, String],
  serieInicial: String,
  inventarioInicial: String,
});

const listaContratos = ref(props.contratos && props.contratos.length > 0 ? props.contratos : (props.contratoActivo ? [props.contratoActivo] : []));

// Sede seleccionada
const sedesDisponibles = ref([]);

const cargarSedes = () => {
    const contrato = listaContratos.value.find(c => c.id === form.contrato_id);
    if (contrato && contrato.cliente && contrato.cliente.sedes) {
        sedesDisponibles.value = contrato.cliente.sedes;
    } else {
        sedesDisponibles.value = [];
    }
};

const form = useForm({
  contrato_id: props.contrato_id_default || props.contratoActivo?.id || (listaContratos.value[0]?.id ?? ''),
  fecha_mantenimiento: (() => {
    const d = new Date();
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().substring(0, 10);
  })(),
  tipo_mantenimiento: 'PREVENTIVO',
  codigo_inventario: props.inventarioInicial || '',
  usuario_asignado: '', // Nombre Completo de Usuario
  tipo_equipo: 'DESKTOP',
  departamento_unidad: '', // Campo para la Unidad
  ubicacion_especifica: '', // Usaremos este campo para la Oficina
  observaciones: '', // Campo para observaciones generales
  imprimir_pdf: false,
  checklist: []
});

const checklistItems = ref([]);

const cargarChecklistsSegunTipo = (tipo) => {
  if (tipo === 'IMPRESORA') {
    checklistItems.value = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza general externa.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de funcionamiento general.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.', realizado: true, comentario: '' }
    ];
  } else if (tipo === 'ESCANER' || tipo === 'ESCÁNER') {
    checklistItems.value = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza externa del escáner', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del cristal', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del alimentador', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de rodillos', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de sensores', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de cables', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Prueba de digitalización', realizado: true, comentario: 'EQUIPO QUEDA FUNCIONANDO' }
    ];
  } else if (tipo === 'LAPTOP') {
    checklistItems.value = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza general externa del equipo (incluyendo todos los accesorios externos).', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del ventilador de salida de aire', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del teclado y el touch pad.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza de lente de CD ROM ó DVD+-RW', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza de los puertos de conectividad.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza de la pantalla de cristal líquido.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Chequeo de voltaje de la fuente de alimentación.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Realizar Diagnósticos al estado de la batería interna de la laptop.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.', realizado: true, comentario: 'EQUIPO QUEDA FUNCIONANDO' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Versión de Windows y Office:', realizado: true, comentario: '' }
    ];
  } else {
    // DESKTOP o ESCRITORIO
    checklistItems.value = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza general externa del equipo (incluye todos los dispositivos externos, monitor, teclado y mouse y demás dispositivos).', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza interna de todos los dispositivos del CPU.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Lubricación y limpieza de ventiladores del chasis y microprocesador.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Verificación del uso adecuado de la memoria (administrador de tareas).', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Chequeo y cambio interno de baterías CMOS.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Chequeo de voltaje de la fuente y aspirado.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.', realizado: true, comentario: 'EQUIPO QUEDA EN FUNCIONAMIENTO' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Versión de Windows y Office:', realizado: true, comentario: '' }
    ];
  }
};

const cambiarTipoEquipo = () => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
};

onMounted(() => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
  cargarSedes();
});

watch(() => form.contrato_id, cargarSedes);

const submitForm = (andPrint = false) => {
  form.imprimir_pdf = andPrint;
  
  // Transformar el checklist al formato que espera el backend
  form.checklist = checklistItems.value.map(item => ({
      ...item,
      estado: item.realizado ? 'SI' : 'NO'
  }));

  form.post(route('mantenimientos.store'));
};
</script>

<template>
  <Head title="Registro Mantenimiento V2" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
            Registro de Mantenimiento (Versión Simplificada)
          </h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            Formato horizontal V2 sin detalles exhaustivos de hardware.
          </p>
        </div>
        <Link :href="route('mantenimientos.index')" class="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
          ← Volver al listado
        </Link>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
      
      <form @submit.prevent="submitForm" class="space-y-6">

        <!-- DATOS PRINCIPALES -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
          <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2">
            📝 Datos Generales
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Contrato *</label>
              <select v-model="form.contrato_id" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500 font-medium">
                <option v-for="c in listaContratos" :key="c.id" :value="c.id">
                  {{ c.nombre_cliente || c.cliente?.nombre_cliente || c.cliente?.nombre_empresa }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha de Atención *</label>
              <input v-model="form.fecha_mantenimiento" type="date" required class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Oficina / Sede *</label>
              <select v-model="form.ubicacion_especifica" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500">
                <option value="" disabled>Seleccione una oficina...</option>
                <!-- Usamos ubicacion_especifica libre si no hay sedes, o un select si hay -->
                <option v-for="sede in sedesDisponibles" :key="sede.id" :value="sede.nombre_sede">
                  {{ sede.nombre_sede }}
                </option>
                <option v-if="sedesDisponibles.length === 0" value="Sin sedes">Debe registrar sedes en el cliente</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Equipo *</label>
              <select v-model="form.tipo_equipo" @change="cambiarTipoEquipo" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500">
                <option value="DESKTOP">DESKTOP</option>
                <option value="LAPTOP">LAPTOP</option>
                <option value="ESCANER">ESCÁNER</option>
                <option value="IMPRESORA">IMPRESORA</option>
                <option value="OTRO">OTRO</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Unidad *</label>
              <input v-model="form.departamento_unidad" type="text" placeholder="Ej. ADJUNTA PARA ASUNTOS INTERNACIONALES" required class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Activo Fijo *</label>
              <input v-model="form.codigo_inventario" type="text" placeholder="Ej. 12345" required class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-900 dark:text-blue-300 rounded-xl focus:ring-blue-500 font-mono font-bold" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre Completo de Usuario *</label>
              <input v-model="form.usuario_asignado" type="text" placeholder="Ej. JUAN PEREZ" required class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500" />
            </div>
          </div>
        </div>

        <!-- CHECKLIST SIMPLIFICADO -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
          <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
            <span>☑️</span> Tareas de Mantenimiento
          </h2>

          <div class="space-y-3">
            <div v-for="(item, idx) in checklistItems" :key="idx" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
              
              <label class="flex items-center gap-3 cursor-pointer w-full sm:w-1/2">
                <input type="checkbox" v-model="item.realizado" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600" />
                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ item.nombre }}</span>
              </label>

              <input v-model="item.comentario" type="text" placeholder="Recomendaciones..." class="w-full sm:w-1/2 text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-3 py-2" />
            </div>
          </div>

          <!-- Observaciones / Daños Previos -->
          <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-4">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Observaciones / Daños Previos</label>
            <textarea v-model="form.observaciones" rows="3" placeholder="Ej. PRESENTÓ DAÑOS EN BATERÍA, EQUIPO EN BUEN ESTADO..." class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500"></textarea>
          </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="submitForm(false)" :disabled="form.processing" class="px-5 py-2.5 text-xs font-bold text-gray-800 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 shadow-sm transition disabled:opacity-50">
            Guardar Solamente
          </button>
          <button type="button" @click="submitForm(true)" :disabled="form.processing" class="px-6 py-2.5 text-xs font-bold text-white bg-emerald-600 dark:bg-emerald-500 rounded-xl hover:bg-emerald-700 dark:hover:bg-emerald-600 shadow-md transition disabled:opacity-50 flex items-center gap-1.5">
            <span>🖨️</span> Guardar e Imprimir PDF
          </button>
        </div>

      </form>
    </div>
  </AuthenticatedLayout>
</template>
