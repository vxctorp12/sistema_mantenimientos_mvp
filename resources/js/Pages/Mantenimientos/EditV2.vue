<script setup>
import { ref, watch, onMounted } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
  mantenimiento: Object,
  contratos: Array,
  departamentos: Array,
});

const form = useForm({
  contrato_id: props.mantenimiento.contrato_id || (props.contratos && props.contratos[0]?.id),
  fecha_mantenimiento: props.mantenimiento.fecha_mantenimiento 
    ? String(props.mantenimiento.fecha_mantenimiento).substring(0, 10) 
    : (() => {
        const d = new Date();
        return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().substring(0, 10);
      })(),
  tipo_mantenimiento: props.mantenimiento.tipo_mantenimiento || 'PREVENTIVO',
  codigo_inventario: props.mantenimiento.equipo?.codigo_inventario || '',
  usuario_asignado: props.mantenimiento.equipo?.usuario_asignado || '', // Nombre Completo de Usuario
  tipo_equipo: props.mantenimiento.equipo?.tipo_equipo || 'DESKTOP',
  departamento_unidad: props.mantenimiento.equipo?.departamento_unidad || '',
  ubicacion_especifica: props.mantenimiento.equipo?.ubicacion_especifica || props.mantenimiento.equipo?.departamento_unidad || '',
  observaciones: props.mantenimiento.observaciones || '',
  impreso: Boolean(props.mantenimiento.impreso),
  checklist: []
});

const sedesDisponibles = ref([]);

const cargarSedes = () => {
    const contrato = props.contratos.find(c => c.id === form.contrato_id);
    if (contrato && contrato.cliente && contrato.cliente.sedes) {
        sedesDisponibles.value = contrato.cliente.sedes;
    } else {
        sedesDisponibles.value = [];
    }
};

const checklistItems = ref([]);

const obtenerItemsExistentesMap = () => {
  const items = (props.mantenimiento.detalles && props.mantenimiento.detalles.length)
    ? props.mantenimiento.detalles 
    : (props.mantenimiento.checklists || []);
    
  const map = {};
  items.forEach(it => {
    const rawKey = (it.item_verificacion || it.punto_chequeo || it.punto || it.nombre || '').toUpperCase().trim();
    if (rawKey) {
      map[rawKey] = {
        estado: it.estado || (it.realizado ? 'SI' : 'NO'),
        comentario: it.comentario || it.comentarios || it.observacion || it.notas || ''
      };
    }
  });
  return map;
};

const buscarMatch = (nombreItem, map) => {
  const normItem = nombreItem.toUpperCase().trim();
  if (map[normItem]) return map[normItem];
  
  const palabras = normItem.split(' ');
  const primeraPalabra = palabras[0];
  if (primeraPalabra.length >= 3) {
    for (const key in map) {
      if (key.includes(primeraPalabra) || normItem.includes(key.split(' ')[0])) {
        return map[key];
      }
    }
  }
  return null;
};

const cargarChecklistsSegunTipo = (tipo) => {
  let baseItems = [];
  
  if (tipo === 'IMPRESORA') {
    baseItems = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza general externa.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de funcionamiento general.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.', realizado: true, comentario: '' }
    ];
  } else if (tipo === 'ESCANER' || tipo === 'ESCÁNER') {
    baseItems = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza externa del escáner', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del cristal', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del alimentador', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de rodillos', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de sensores', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Revisión de cables', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Prueba de digitalización', realizado: true, comentario: 'EQUIPO QUEDA FUNCIONANDO' }
    ];
  } else if (tipo === 'LAPTOP') {
    baseItems = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza general externa del equipo (incluyendo todos los accesorios externos).', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del ventilador de salida de aire', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza del teclado y el touch pad.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza de lente de CD ROM ó DVD+-RW', realizado: false, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza de los puertos de conectividad.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza de la pantalla de cristal líquido.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Chequeo de voltaje de la fuente de alimentación.', realizado: false, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Realizar Diagnósticos al estado de la batería interna de la laptop.', realizado: false, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.', realizado: true, comentario: 'EQUIPO QUEDA FUNCIONANDO' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Versión de Windows y Office:', realizado: false, comentario: '' }
    ];
  } else {
    baseItems = [
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza general externa del equipo (incluye todos los dispositivos externos, monitor, teclado y mouse y demás dispositivos).', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Limpieza interna de todos los dispositivos del CPU.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Lubricación y limpieza de ventiladores del chasis y microprocesador.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Verificación del uso adecuado de la memoria (administrador de tareas).', realizado: false, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Chequeo y cambio interno de baterías CMOS.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Chequeo de voltaje de la fuente y aspirado.', realizado: true, comentario: '' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.', realizado: true, comentario: 'EQUIPO QUEDA EN FUNCIONAMIENTO' },
      { seccion: 'MANTENIMIENTO PREVENTIVO', nombre: 'Versión de Windows y Office:', realizado: false, comentario: '' }
    ];
  }

  const map = obtenerItemsExistentesMap();
  checklistItems.value = baseItems.map(item => {
    const match = buscarMatch(item.nombre, map);
    return {
      ...item,
      realizado: match ? (match.estado === 'SI') : item.realizado,
      comentario: match ? match.comentario : item.comentario
    };
  });
};

const cambiarTipoEquipo = () => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
};

onMounted(() => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
  cargarSedes();
});

watch(() => form.contrato_id, cargarSedes);

const submit = () => {
  form.checklist = checklistItems.value.map(item => ({
      ...item,
      estado: item.realizado ? 'SI' : 'NO'
  }));

  form.put(route('mantenimientos.update', props.mantenimiento.id));
};
</script>

<template>
  <Head title="Modificar Mantenimiento V2" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              Modificar Mantenimiento V2 #{{ mantenimiento.id }}
            </h2>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full" 
                  :class="form.impreso ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'">
              {{ form.impreso ? '🖨️ Impreso / Firmado' : '⏳ Pendiente Impresión' }}
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
            Edición simplificada (Versión V2)
          </p>
        </div>

        <div class="flex items-center gap-2">
          <a :href="route('mantenimientos.pdf', mantenimiento.id)" target="_blank"
             class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5">
            <span>🖨️ Generar / Imprimir PDF</span>
          </a>
          <Link :href="route('mantenimientos.index')" 
                class="px-3.5 py-2 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 text-xs font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-700 transition">
            ← Volver al Listado
          </Link>
        </div>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
      
      <form @submit.prevent="submit" class="space-y-6">

        <!-- DATOS PRINCIPALES -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
          <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2">
            📝 Datos Generales
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Contrato *</label>
              <select v-model="form.contrato_id" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500 font-medium">
                <option v-for="c in contratos" :key="c.id" :value="c.id">
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
                <!-- Retener valor actual si no está en la lista de sedes pero existe -->
                <option v-if="sedesDisponibles.length > 0 && form.ubicacion_especifica && !sedesDisponibles.find(s => s.nombre_sede === form.ubicacion_especifica)" :value="form.ubicacion_especifica">
                  {{ form.ubicacion_especifica }} (Actual)
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Equipo *</label>
              <select v-model="form.tipo_equipo" @change="cambiarTipoEquipo" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500">
                <option value="DESKTOP">DESKTOP</option>
                <option value="LAPTOP">LAPTOP</option>
                <option value="ESCANER">ESCÁNER</option>
                <option value="IMPRESORA">IMPRESORA</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Unidad *</label>
              <input v-model="form.departamento_unidad" type="text" list="departamentos-list-v2" placeholder="Ej. ADJUNTA PARA ASUNTOS INTERNACIONALES" required class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500" />
              <datalist id="departamentos-list-v2">
                  <option v-for="d in departamentos" :key="d" :value="d"></option>
              </datalist>
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
        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-zinc-800">
          <a :href="route('mantenimientos.pdf', mantenimiento.id)" target="_blank" 
             class="px-4 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-semibold rounded-lg transition flex items-center gap-2">
            🖨️ Generar / Imprimir PDF Hoja de Servicio
          </a>

          <div class="flex items-center gap-3">
            <Link :href="route('mantenimientos.index')" 
                  class="px-4 py-2.5 bg-gray-200 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 text-xs font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-700 transition">
              Cancelar
            </Link>
            <button type="submit" :disabled="form.processing" 
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg shadow-sm transition disabled:opacity-50">
              Guardar Cambios Registrados
            </button>
          </div>
        </div>

      </form>
    </div>
  </AuthenticatedLayout>
</template>
