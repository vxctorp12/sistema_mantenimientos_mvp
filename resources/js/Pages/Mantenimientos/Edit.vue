<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  mantenimiento: Object,
  contratos: Array
});

// LISTAS PREDEFINIDAS BASADAS AL 100% EN FORMATOS FÍSICOS OFICIALES Y REPORTES PDF
const getImpresoraTemplates = () => ({
  previo: [
    { seccion: 'PREVIO', nombre: 'Verificar condiciones físicas: manchas, rayones, golpes, cables dañados, fugas de tóner/tinta y estado general.', estado: 'SI' },
    { seccion: 'PREVIO', nombre: 'Encender el equipo y comprobar panel, alimentación eléctrica, conectividad y funciones principales.', estado: 'SI' },
    { seccion: 'PREVIO', nombre: 'Realizar prueba inicial de impresión/copia/escaneo y registrar contadores antes del mantenimiento.', estado: 'SI' }
  ],
  externa: [
    { seccion: 'EXTERNA', nombre: 'Cubiertas y carcasa', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Panel de control / Pantalla', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Bandejas de papel y Bypass', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'ADF / Cristal / Área de escaneo', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Cables y conectores', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Otros (Especifique)', estado: 'N/A', comentario: '' }
  ],
  interna: [
    { seccion: 'INTERNA', nombre: 'Consumibles en buen estado', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Revisión / Limpieza de rodillos', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Estado de fusor / Cabezal', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Faja / Unidad de imagen', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Lubricación mecánica', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Limpieza interna general', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Impresión desde el equipo', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Impresión desde computadora', estado: 'SI', comentario: '' }
  ],
  post: [
    { seccion: 'POSTERIOR', nombre: 'Verificar funcionamiento mediante pruebas de impresión, copia/escaneo, conectividad y calidad de salida.', estado: 'SI' }
  ]
});

const getComputoTemplates = () => ({
  previo: [
    { seccion: 'PREVIO', nombre: 'Verificar condiciones físicas: manchas, rayones, fisuras, golpes, cables reventados y estado general del equipo.', estado: 'SI' },
    { seccion: 'PREVIO', nombre: 'Encender el equipo y comprobar que el hardware y el sistema operativo funcionan correctamente.', estado: 'SI' },
    { seccion: 'PREVIO', nombre: 'Registrar cualquier condición previa o componente dañado antes del mantenimiento.', estado: 'SI' }
  ],
  externa: [
    { seccion: 'EXTERNA', nombre: 'Monitor / Pantalla', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Teclado', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Mouse / Mousepad', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Cables y conectores de alimentación', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Case / Puertos / Lectores', estado: 'SI', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Unidad óptica CD/DVD ROM', estado: 'N/A', comentario: '' },
    { seccion: 'EXTERNA', nombre: 'Otros (Especifique)', estado: 'N/A', comentario: '' }
  ],
  interna: [
    { seccion: 'INTERNA', nombre: 'Motherboard', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Cambio de pasta térmica', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Memorias RAM', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Disco Duro HDD / SSD', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Tarjetas de Red / Video', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Ventiladores / Microprocesador', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Fuente de poder', estado: 'SI', comentario: '' },
    { seccion: 'INTERNA', nombre: 'Otros (Especifique)', estado: 'N/A', comentario: '' }
  ],
  post: [
    { seccion: 'POSTERIOR', nombre: 'Encender el equipo y verificar funcionamiento del hardware, sistema operativo, conectividad y ajustes visuales de monitor.', estado: 'SI' }
  ]
});

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

const checklistPrevio = ref([]);
const checklistExterna = ref([]);
const checklistInterna = ref([]);
const checklistPost = ref([]);

const cargarChecklistsSegunTipo = (tipo) => {
  const templates = tipo === 'IMPRESORA' ? getImpresoraTemplates() : getComputoTemplates();
  const map = obtenerItemsExistentesMap();

  checklistPrevio.value = templates.previo.map(item => {
    const match = buscarMatch(item.nombre, map);
    return {
      ...item,
      estado: match ? match.estado : item.estado
    };
  });

  checklistExterna.value = templates.externa.map(item => {
    const match = buscarMatch(item.nombre, map);
    return {
      ...item,
      estado: match ? match.estado : item.estado,
      comentario: match ? match.comentario : (item.comentario || '')
    };
  });

  checklistInterna.value = templates.interna.map(item => {
    const match = buscarMatch(item.nombre, map);
    return {
      ...item,
      estado: match ? match.estado : item.estado,
      comentario: match ? match.comentario : (item.comentario || '')
    };
  });

  checklistPost.value = templates.post.map(item => {
    const match = buscarMatch(item.nombre, map);
    return {
      ...item,
      estado: match ? match.estado : item.estado
    };
  });
};

const form = useForm({
  // Datos del Equipo
  codigo_inventario: props.mantenimiento.equipo?.codigo_inventario || '',
  numero_serie: props.mantenimiento.equipo?.numero_serie || '',
  tipo_equipo: props.mantenimiento.equipo?.tipo_equipo || 'DESKTOP',
  marca: props.mantenimiento.equipo?.marca || '',
  modelo: props.mantenimiento.equipo?.modelo || '',
  ubicacion_especifica: props.mantenimiento.equipo?.ubicacion_especifica || props.mantenimiento.equipo?.departamento_unidad || '',
  usuario_asignado: props.mantenimiento.equipo?.usuario_asignado || '',

  // Datos del Mantenimiento
  contrato_id: props.mantenimiento.contrato_id || (props.contratos && props.contratos[0]?.id),
  fecha_mantenimiento: props.mantenimiento.fecha_mantenimiento 
    ? String(props.mantenimiento.fecha_mantenimiento).substring(0, 10) 
    : (() => {
        const d = new Date();
        return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().substring(0, 10);
      })(),
  tipo_mantenimiento: props.mantenimiento.tipo_mantenimiento || 'PREVENTIVO',
  contador_bn: props.mantenimiento.contador_bn || 0,
  contador_color: props.mantenimiento.contador_color || 0,
  direccion_ip: props.mantenimiento.direccion_ip || props.mantenimiento.equipo?.direccion_ip || '',
  recomendaciones: props.mantenimiento.recomendaciones || '',
  observaciones: props.mantenimiento.observaciones || '',
  estado_equipo: props.mantenimiento.estado_equipo || 'OPERATIVO',
  estado_firma: props.mantenimiento.estado_firma || 'PENDIENTE',
  impreso: Boolean(props.mantenimiento.impreso),

  // Checklist final
  checklist: []
});

const cambiarTipoEquipo = () => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
};

onMounted(() => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
});

const submit = () => {
  form.checklist = [
    ...checklistPrevio.value,
    ...checklistExterna.value,
    ...checklistInterna.value,
    ...checklistPost.value
  ];
  form.put(route('mantenimientos.update', props.mantenimiento.id));
};
</script>

<template>
  <Head title="Modificar Mantenimiento v4" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              Modificar Reporte de Mantenimiento #{{ mantenimiento.id }}
            </h2>
            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full" 
                  :class="form.impreso ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'">
              {{ form.impreso ? '🖨️ Impreso / Firmado' : '⏳ Pendiente Impresión' }}
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
            Edición total de datos del equipo, atención técnica y checklist físico (v4)
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

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- SECCIÓN 1: DATOS DEL EQUIPO (EDITABLE TOTAL) -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-gray-200 dark:border-zinc-800 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-gray-100 dark:border-zinc-800 pb-3">
            <h3 class="font-bold text-sm text-blue-600 dark:text-blue-400 flex items-center gap-2">
              💻 Información del Equipo (Búsqueda Principal: Activo Fijo / Inventario)
            </h3>
            <span class="text-[11px] text-gray-400 dark:text-zinc-500">Los cambios actualizarán la ficha del equipo</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Código Inventario / Activo Fijo *
              </label>
              <input v-model="form.codigo_inventario" type="text" required 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono font-bold" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Número de Serie *
              </label>
              <input v-model="form.numero_serie" type="text" required 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono font-bold" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Tipo de Equipo *
              </label>
              <select v-model="form.tipo_equipo" @change="cambiarTipoEquipo" 
                      class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="DESKTOP">🖥️ DESKTOP</option>
                <option value="LAPTOP">💻 LAPTOP</option>
                <option value="IMPRESORA">🖨️ IMPRESORA</option>
                <option value="OTRO">📦 OTRO</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Marca *
              </label>
              <input v-model="form.marca" type="text" required 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Modelo *
              </label>
              <input v-model="form.modelo" type="text" required 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Usuario Asignado / Responsable
              </label>
              <input v-model="form.usuario_asignado" type="text" 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
            </div>

            <div class="md:col-span-3">
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Ubicación Específica / Departamento
              </label>
              <input v-model="form.ubicacion_especifica" type="text" 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
        </div>

        <!-- SECCIÓN 2: DATOS DE LA ATENCIÓN TÉCNICA -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-gray-200 dark:border-zinc-800 shadow-sm space-y-4">
          <h3 class="font-bold text-sm text-gray-800 dark:text-zinc-200 border-b border-gray-100 dark:border-zinc-800 pb-3">
            📋 Datos de la Atención Técnica
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Contrato Asociado *
              </label>
              <select v-model="form.contrato_id" required 
                      class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option v-for="c in contratos" :key="c.id" :value="c.id">
                  {{ c.cliente?.nombre_cliente }} - {{ c.ubicacion_general || 'Sede Principal' }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Fecha de Atención *
              </label>
              <input v-model="form.fecha_mantenimiento" type="date" required 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Tipo de Mantenimiento *
              </label>
              <select v-model="form.tipo_mantenimiento" required 
                      class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="PREVENTIVO">🛠️ PREVENTIVO</option>
                <option value="CORRECTIVO">🚨 CORRECTIVO</option>
              </select>
            </div>
          </div>

          <!-- Campos específicos para Impresoras -->
          <div v-if="form.tipo_equipo === 'IMPRESORA'" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-gray-100 dark:border-zinc-800">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Contador Blanco & Negro
              </label>
              <input v-model.number="form.contador_bn" type="number" min="0" 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Contador Color
              </label>
              <input v-model.number="form.contador_color" type="number" min="0" 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Dirección IP de Red
              </label>
              <input v-model="form.direccion_ip" type="text" placeholder="192.168.1.50" 
                     class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-mono" />
            </div>
          </div>
        </div>

        <!-- SECCIÓN 3: CHECKLIST DE MANTENIMIENTO PREVENTIVO (OFICIAL 4 SECCIONES) -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-gray-200 dark:border-zinc-800 shadow-sm space-y-6">
          <div class="flex justify-between items-center border-b border-gray-100 dark:border-zinc-800 pb-3">
            <h3 class="font-bold text-sm text-gray-800 dark:text-zinc-200 flex items-center gap-2">
              <span>☑️</span> Checklist de Mantenimiento Preventivo (Formato {{ form.tipo_equipo === 'IMPRESORA' ? 'Físico Impresoras' : 'Físico Cómputo' }})
            </h3>
            <span class="text-xs text-gray-400 dark:text-zinc-500 italic">Ítems oficiales del reporte PDF</span>
          </div>

          <!-- 1. PUNTOS DE CHEQUEO PREVIO -->
          <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800/40 space-y-3">
            <h4 class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider">1. Puntos de Chequeo Previo</h4>
            <div class="space-y-2">
              <div v-for="(item, idx) in checklistPrevio" :key="'prev-'+idx" class="flex flex-col sm:flex-row sm:items-center justify-between bg-white dark:bg-zinc-800 p-3 rounded-xl border border-amber-100 dark:border-zinc-700 text-xs gap-2">
                <span class="font-medium text-gray-800 dark:text-zinc-200">{{ item.nombre }}</span>
                <div class="flex items-center gap-3 whitespace-nowrap">
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'prev-'+idx" value="SI" v-model="item.estado" class="text-blue-600 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600" />
                    <span class="text-xs text-gray-700 dark:text-zinc-300 font-semibold">CUMPLE</span>
                  </label>
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'prev-'+idx" value="NO" v-model="item.estado" class="text-red-600 focus:ring-red-500 dark:bg-zinc-700 dark:border-zinc-600" />
                    <span class="text-xs text-red-600 dark:text-red-400 font-semibold">OBSERVACIÓN</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. REVISIÓN Y LIMPIEZA EXTERNA -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider border-b border-gray-100 dark:border-zinc-800 pb-1">2. Revisión y Limpieza Externa</h4>
            <div class="divide-y divide-gray-100 dark:divide-zinc-800 border border-gray-100 dark:border-zinc-800 rounded-xl overflow-hidden">
              <div v-for="(item, idx) in checklistExterna" :key="'ext-'+idx" class="p-3 bg-gray-50 dark:bg-zinc-800/50 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="w-full md:w-1/3 font-semibold text-gray-700 dark:text-zinc-200">{{ item.nombre }}</div>
                <div class="flex items-center gap-4 text-gray-700 dark:text-zinc-300">
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'ext-'+idx" value="SI" v-model="item.estado" class="text-blue-600 dark:bg-zinc-700" /> SI</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'ext-'+idx" value="NO" v-model="item.estado" class="text-red-600 dark:bg-zinc-700" /> NO</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'ext-'+idx" value="N/A" v-model="item.estado" class="text-gray-500 dark:bg-zinc-700" /> N/A</label>
                </div>
                <input v-model="item.comentario" type="text" placeholder="Comentarios u observaciones..." class="w-full md:w-1/2 text-xs border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
          </div>

          <!-- 3. MANTENIMIENTO E INSPECCIÓN INTERNA -->
          <div class="space-y-3">
            <h4 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider border-b border-gray-100 dark:border-zinc-800 pb-1">3. Mantenimiento e Inspección Interna</h4>
            <div class="divide-y divide-gray-100 dark:divide-zinc-800 border border-gray-100 dark:border-zinc-800 rounded-xl overflow-hidden">
              <div v-for="(item, idx) in checklistInterna" :key="'int-'+idx" class="p-3 bg-gray-50 dark:bg-zinc-800/50 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="w-full md:w-1/3 font-semibold text-gray-700 dark:text-zinc-200">{{ item.nombre }}</div>
                <div class="flex items-center gap-4 text-gray-700 dark:text-zinc-300">
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'int-'+idx" value="SI" v-model="item.estado" class="text-blue-600 dark:bg-zinc-700" /> SI</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'int-'+idx" value="NO" v-model="item.estado" class="text-red-600 dark:bg-zinc-700" /> NO</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'int-'+idx" value="N/A" v-model="item.estado" class="text-gray-500 dark:bg-zinc-700" /> N/A</label>
                </div>
                <input v-model="item.comentario" type="text" placeholder="Comentarios u observaciones..." class="w-full md:w-1/2 text-xs border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
          </div>

          <!-- 4. PUNTOS DE CHEQUEO DESPUÉS DEL MANTENIMIENTO -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800/40 space-y-3">
            <h4 class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase tracking-wider">4. Puntos de Chequeo Después del Mantenimiento</h4>
            <div class="space-y-2">
              <div v-for="(item, idx) in checklistPost" :key="'post-'+idx" class="flex flex-col sm:flex-row sm:items-center justify-between bg-white dark:bg-zinc-800 p-3 rounded-xl border border-blue-100 dark:border-zinc-700 text-xs gap-2">
                <span class="font-medium text-gray-800 dark:text-zinc-200">{{ item.nombre }}</span>
                <div class="flex items-center gap-3 whitespace-nowrap">
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'post-'+idx" value="SI" v-model="item.estado" class="text-blue-600 focus:ring-blue-500 dark:bg-zinc-700 dark:border-zinc-600" />
                    <span class="text-xs text-gray-700 dark:text-zinc-300 font-semibold">VERIFICADO Y OPERATIVO</span>
                  </label>
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'post-'+idx" value="NO" v-model="item.estado" class="text-red-600 focus:ring-red-500 dark:bg-zinc-700 dark:border-zinc-600" />
                    <span class="text-xs text-red-600 dark:text-red-400 font-semibold">PENDIENTE</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- SECCIÓN 4: DIAGNÓSTICO, TRABAJO REALIZADO Y ESTADO FINAL -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-gray-200 dark:border-zinc-800 shadow-sm space-y-4">
          <h3 class="font-bold text-sm text-gray-800 dark:text-zinc-200 border-b border-gray-100 dark:border-zinc-800 pb-3">
            📝 Diagnóstico y Trabajo Ejecutado
          </h3>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Revisión previa del equipo antes del mantenimiento
              </label>
              <textarea v-model="form.recomendaciones" rows="3" 
                        placeholder="Condición del equipo al iniciar la atención..." 
                        class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">
                Observaciones Generales
              </label>
              <textarea v-model="form.observaciones" rows="2" 
                        placeholder="Notas adicionales o comentarios generales..." 
                        class="w-full bg-gray-50 dark:bg-zinc-800/80 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-gray-100 dark:border-zinc-800">
            <div>
              <label class="block text-xs font-bold text-gray-800 dark:text-zinc-200 mb-1">
                Estado Final del Equipo *
              </label>
              <select v-model="form.estado_equipo" 
                      class="w-full bg-gray-50 dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="OPERATIVO">🟢 OPERATIVO (Entregado al cliente)</option>
                <option value="REQUIERE_REPARACION">🟡 REQUIERE REPARACIÓN / REPUESTO</option>
                <option value="FUERA_DE_SERVICIO">🔴 FUERA DE SERVICIO (Baja)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-800 dark:text-zinc-200 mb-1">
                Estado de Firma Físico
              </label>
              <select v-model="form.estado_firma" 
                      class="w-full bg-gray-50 dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg py-2 px-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="PENDIENTE">⏳ PENDIENTE (Borrador)</option>
                <option value="FIRMADO_FISICO">✅ FIRMADO_FISICO (Conforme)</option>
              </select>
            </div>
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
