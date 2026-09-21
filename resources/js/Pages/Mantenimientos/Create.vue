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

const buscando = ref(false);
const mensajeBusqueda = ref('');

const listaContratos = ref(props.contratos && props.contratos.length > 0 ? props.contratos : (props.contratoActivo ? [props.contratoActivo] : []));

const form = useForm({
  contrato_id: props.contrato_id_default || props.contratoActivo?.id || (listaContratos.value[0]?.id ?? ''),
  fecha_mantenimiento: (() => {
    const d = new Date();
    return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().substring(0, 10);
  })(),
  tipo_mantenimiento: 'PREVENTIVO',
  codigo_inventario: props.inventarioInicial || '',
  numero_serie: props.serieInicial || '',
  tipo_equipo: 'DESKTOP',
  marca: '',
  modelo: '',
  departamento_unidad: '',
  usuario_asignado: '',
  direccion_ip: '',
  sistema_operativo: '',
  contador_bn: null,
  contador_color: null,
  observaciones: '',
  recomendaciones: '',
  imprimir_pdf: false,
  checklist: []
});

// LISTAS DE VERIFICACIÓN BASADAS AL 100% EN FORMATOS FÍSICOS RECTIFICADOS
const checklistPrevio = ref([]);
const checklistExterna = ref([]);
const checklistInterna = ref([]);
const checklistPost = ref([]);

const cargarChecklistsSegunTipo = (tipo) => {
  if (tipo === 'IMPRESORA') {
    // FORMATO FÍSICO IMPRESORAS
    checklistPrevio.value = [
      { seccion: 'PREVIO', nombre: 'Verificar condiciones físicas: manchas, rayones, golpes, cables dañados, fugas de tóner/tinta y estado general.', estado: 'SI' },
      { seccion: 'PREVIO', nombre: 'Encender el equipo y comprobar panel, alimentación eléctrica, conectividad y funciones principales.', estado: 'SI' },
      { seccion: 'PREVIO', nombre: 'Realizar prueba inicial de impresión/copia/escaneo y registrar contadores antes del mantenimiento.', estado: 'SI' }
    ];

    checklistExterna.value = [
      { seccion: 'EXTERNA', nombre: 'Cubiertas y carcasa', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Panel de control / Pantalla', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Bandejas de papel y Bypass', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'ADF / Cristal / Área de escaneo', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Cables y conectores', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Otros (Especifique)', estado: 'N/A', comentario: '' }
    ];

    checklistInterna.value = [
      { seccion: 'INTERNA', nombre: 'Consumibles en buen estado', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Revisión / Limpieza de rodillos', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Estado de fusor / Cabezal', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Faja / Unidad de imagen', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Lubricación mecánica', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Limpieza interna general', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Impresión desde el equipo', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Impresión desde computadora', estado: 'SI', comentario: '' }
    ];

    checklistPost.value = [
      { seccion: 'POSTERIOR', nombre: 'Verificar funcionamiento mediante pruebas de impresión, copia/escaneo, conectividad y calidad de salida.', estado: 'SI' }
    ];

  } else {
    // FORMATO FÍSICO CÓMPUTO (DESKTOP/LAPTOP)
    checklistPrevio.value = [
      { seccion: 'PREVIO', nombre: 'Verificar condiciones físicas: manchas, rayones, fisuras, golpes, cables reventados y estado general del equipo.', estado: 'SI' },
      { seccion: 'PREVIO', nombre: 'Encender el equipo y comprobar que el hardware y el sistema operativo funcionan correctamente.', estado: 'SI' },
      { seccion: 'PREVIO', nombre: 'Registrar cualquier condición previa o componente dañado antes de iniciar el mantenimiento.', estado: 'SI' }
    ];

    checklistExterna.value = [
      { seccion: 'EXTERNA', nombre: 'Monitor / Pantalla', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Teclado', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Mouse / Mousepad', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Cables y conectores de alimentación', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Case / Puertos / Lectores', estado: 'SI', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Unidad óptica CD/DVD ROM', estado: 'N/A', comentario: '' },
      { seccion: 'EXTERNA', nombre: 'Otros (Especifique)', estado: 'N/A', comentario: '' }
    ];

    checklistInterna.value = [
      { seccion: 'INTERNA', nombre: 'Motherboard', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Cambio de pasta térmica', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Memorias RAM', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Disco Duro HDD / SSD', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Tarjetas de Red / Video', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Ventiladores / Microprocesador', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Fuente de poder', estado: 'SI', comentario: '' },
      { seccion: 'INTERNA', nombre: 'Otros (Especifique)', estado: 'N/A', comentario: '' }
    ];

    checklistPost.value = [
      { seccion: 'POSTERIOR', nombre: 'Encender el equipo y verificar funcionamiento del hardware, sistema operativo, conectividad y ajustes visuales de monitor.', estado: 'SI' }
    ];
  }
};

const cambiarTipoEquipo = () => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
};

const buscarEquipo = async () => {
  const query = form.codigo_inventario || form.numero_serie;
  if (!query || query.length < 2) return;
  buscando.value = true;
  mensajeBusqueda.value = '';

  try {
    const res = await axios.get(route('equipos.buscar-inventario'), {
      params: { codigo_inventario: query }
    });
    if (res.data.encontrado && res.data.equipo) {
      const eq = res.data.equipo;
      form.codigo_inventario = eq.codigo_inventario || form.codigo_inventario;
      form.numero_serie = eq.numero_serie || form.numero_serie;
      form.tipo_equipo = eq.tipo_equipo || form.tipo_equipo;
      form.marca = eq.marca || form.marca;
      form.modelo = eq.modelo || form.modelo;
      form.departamento_unidad = eq.departamento_unidad || form.departamento_unidad;
      form.usuario_asignado = eq.usuario_asignado || form.usuario_asignado;
      form.direccion_ip = eq.direccion_ip || form.direccion_ip;
      form.sistema_operativo = eq.sistema_operativo || form.sistema_operativo;
      
      cargarChecklistsSegunTipo(form.tipo_equipo);
      mensajeBusqueda.value = '✓ Datos del equipo cargados automáticamente.';
    } else {
      // Intentar por serie secundaria
      const resSerie = await axios.get(route('equipos.buscar', query));
      if (resSerie.data.encontrado && resSerie.data.equipo) {
        const eq = resSerie.data.equipo;
        form.codigo_inventario = eq.codigo_inventario || form.codigo_inventario;
        form.numero_serie = eq.numero_serie || form.numero_serie;
        form.tipo_equipo = eq.tipo_equipo || form.tipo_equipo;
        form.marca = eq.marca || form.marca;
        form.modelo = eq.modelo || form.modelo;
        form.departamento_unidad = eq.departamento_unidad || form.departamento_unidad;
        form.usuario_asignado = eq.usuario_asignado || form.usuario_asignado;
        form.direccion_ip = eq.direccion_ip || form.direccion_ip;
        form.sistema_operativo = eq.sistema_operativo || form.sistema_operativo;

        cargarChecklistsSegunTipo(form.tipo_equipo);
        mensajeBusqueda.value = '✓ Datos del equipo cargados automáticamente por serie.';
      } else {
        mensajeBusqueda.value = 'ℹ Equipo nuevo. Ingresa las especificaciones requeridas.';
      }
    }
  } catch (e) {
    console.error('Error al buscar equipo:', e);
  } finally {
    buscando.value = false;
  }
};

onMounted(() => {
  cargarChecklistsSegunTipo(form.tipo_equipo);
  if (form.codigo_inventario || form.numero_serie) {
    buscarEquipo();
  }
});

const submitForm = (andPrint = false) => {
  form.imprimir_pdf = andPrint;
  form.checklist = [
    ...checklistPrevio.value,
    ...checklistExterna.value,
    ...checklistInterna.value,
    ...checklistPost.value
  ];
  form.post(route('mantenimientos.store'));
};
</script>

<template>
  <Head title="Hoja de Asistencia Técnica en Sitio" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
            Hoja de Asistencia Técnica en Sitio (v3)
          </h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            Registro de Mantenimiento Preventivo con Validación por Activo Fijo / Serie
          </p>
        </div>
        <Link :href="route('mantenimientos.index')" class="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
          ← Volver al listado
        </Link>
      </div>
    </template>

    <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
      <!-- Encabezado del Formulario -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-lg font-bold text-gray-900 dark:text-white">Formulario Único de Atención Preventiva</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Basado al 100% en los formatos físicos oficiales de servicio RILAZ</p>
        </div>
        <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
          Formato {{ form.tipo_equipo === 'IMPRESORA' ? 'Físico Impresoras' : 'Físico Equipo Cómputo' }}
        </span>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">

        <!-- SECCIÓN 1: Selección de Contrato y Búsqueda por Código de Inventario -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
          <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
            <span>📋</span> Datos del Contrato e Identificación Principal
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Selección de Contrato -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Contrato Activo *</label>
              <select v-model="form.contrato_id" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500 font-medium">
                <option v-for="c in listaContratos" :key="c.id" :value="c.id">
                  {{ c.nombre_cliente || c.cliente?.nombre_cliente || c.cliente?.nombre_empresa }} - {{ c.ubicacion_general || 'Sede Principal' }}
                </option>
              </select>
            </div>

            <!-- Fecha de Mantenimiento -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha de Atención *</label>
              <input 
                v-model="form.fecha_mantenimiento" 
                type="date" 
                required
                class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500" 
              />
            </div>

            <!-- Tipo de Mantenimiento -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Mantenimiento *</label>
              <select v-model="form.tipo_mantenimiento" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-blue-500 font-medium font-bold">
                <option value="PREVENTIVO">🛠️ PREVENTIVO</option>
                <option value="CORRECTIVO">🚨 CORRECTIVO</option>
                <option value="INSTALACION">📦 INSTALACIÓN</option>
                <option value="RETIRO">🚫 RETIRO</option>
              </select>
            </div>

            <!-- Identificador Principal: Código de Inventario / Activo Fijo -->
            <div class="md:col-span-2">
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Código de Inventario / Activo Fijo *</label>
              <div class="flex gap-2">
                <input 
                  v-model="form.codigo_inventario" 
                  @blur="buscarEquipo"
                  @keyup.enter="buscarEquipo"
                  type="text" 
                  placeholder="Ej. 0196-0352" 
                  required
                  class="w-full text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-900 dark:text-blue-300 rounded-xl focus:ring-blue-500 font-mono font-bold" 
                />
                <button 
                  type="button" 
                  @click="buscarEquipo" 
                  :disabled="buscando"
                  class="px-4 py-2 text-xs font-bold bg-blue-600 dark:bg-blue-500 text-white rounded-xl hover:bg-blue-700 transition disabled:opacity-50 whitespace-nowrap"
                >
                  {{ buscando ? 'Buscando...' : 'Buscar' }}
                </button>
              </div>
              <span v-if="mensajeBusqueda" class="text-xs font-medium text-blue-600 dark:text-blue-400 mt-1 block">
                {{ mensajeBusqueda }}
              </span>
              <p v-else class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Ingresa la placa/activo fijo para autocompletar las especificaciones registradas.</p>
            </div>
          </div>
        </div>

        <!-- SECCIÓN 2: Información del Equipo (Físico vs Cómputo) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
          <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
            <span>💻</span> Especificaciones del Equipo
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Equipo *</label>
              <select v-model="form.tipo_equipo" @change="cambiarTipoEquipo" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl">
                <option value="DESKTOP">DESKTOP</option>
                <option value="LAPTOP">LAPTOP</option>
                <option value="IMPRESORA">IMPRESORA</option>
                <option value="OTRO">OTRO</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Número de Serie *</label>
              <input v-model="form.numero_serie" type="text" placeholder="Ej. 5302X248936" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl font-mono font-bold" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Marca *</label>
              <input v-model="form.marca" type="text" placeholder="Ej. LENOVO, HP, RICOH" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Modelo *</label>
              <input v-model="form.modelo" type="text" placeholder="Ej. M70Q, P800" required class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Ubicación / Área / Depto.</label>
              <input v-model="form.departamento_unidad" type="text" placeholder="Ej. GERENCIA INTERNACIONAL" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Usuario Responsable</label>
              <input v-model="form.usuario_asignado" type="text" placeholder="Ej. MARIO PEREZ" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl" />
            </div>

            <!-- Campos Específicos según Formato Físico -->
            <div v-if="form.tipo_equipo !== 'IMPRESORA'">
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sistema Operativo</label>
              <input v-model="form.sistema_operativo" type="text" placeholder="Ej. Windows 11 Pro" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dirección IP</label>
              <input v-model="form.direccion_ip" type="text" placeholder="Ej. 192.168.1.50" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl font-mono" />
            </div>

            <!-- Contadores para Impresoras -->
            <template v-if="form.tipo_equipo === 'IMPRESORA'">
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Contador B/N</label>
                <input v-model.number="form.contador_bn" type="number" placeholder="0" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl font-mono" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Contador Color</label>
                <input v-model.number="form.contador_color" type="number" placeholder="0" class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl font-mono" />
              </div>
            </template>
          </div>
        </div>

        <!-- SECCIÓN 3: LISTA DE VERIFICACIÓN DINÁMICA (FORMATOS FÍSICOS REALES) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-6">
          <div class="flex justify-between items-center border-b border-gray-100 dark:border-gray-700 pb-3">
            <h2 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <span>☑️</span> Checklist de Mantenimiento Preventivo
            </h2>
            <span class="text-xs text-gray-400 dark:text-gray-500 italic">Ítems oficiales del formato de servicio</span>
          </div>

          <!-- 1. PUNTOS DE CHEQUEO PREVIO -->
          <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800/40 space-y-3">
            <h3 class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider">1. Puntos de Chequeo Previo</h3>
            <div class="space-y-2">
              <div v-for="(item, idx) in checklistPrevio" :key="'prev-'+idx" class="flex flex-col sm:flex-row sm:items-center justify-between bg-white dark:bg-gray-800 p-3 rounded-xl border border-amber-100 dark:border-gray-700 text-xs gap-2">
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ item.nombre }}</span>
                <div class="flex items-center gap-3 whitespace-nowrap">
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'prev-'+idx" value="SI" v-model="item.estado" class="text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" />
                    <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">CUMPLE</span>
                  </label>
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'prev-'+idx" value="NO" v-model="item.estado" class="text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600" />
                    <span class="text-xs text-red-600 dark:text-red-400 font-semibold">OBSERVACIÓN</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. REVISIÓN Y LIMPIEZA EXTERNA -->
          <div class="space-y-3">
            <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 pb-1">2. Revisión y Limpieza Externa</h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
              <div v-for="(item, idx) in checklistExterna" :key="'ext-'+idx" class="p-3 bg-gray-50 dark:bg-gray-700/50 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="w-full md:w-1/3 font-semibold text-gray-700 dark:text-gray-200">{{ item.nombre }}</div>
                <div class="flex items-center gap-4 text-gray-700 dark:text-gray-300">
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'ext-'+idx" value="SI" v-model="item.estado" class="text-blue-600 dark:bg-gray-700" /> SI</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'ext-'+idx" value="NO" v-model="item.estado" class="text-red-600 dark:bg-gray-700" /> NO</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'ext-'+idx" value="N/A" v-model="item.estado" class="text-gray-500 dark:bg-gray-700" /> N/A</label>
                </div>
                <input v-model="item.comentario" type="text" placeholder="Comentarios u observaciones..." class="w-full md:w-1/2 text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-2 py-1" />
              </div>
            </div>
          </div>

          <!-- 3. MANTENIMIENTO E INSPECCIÓN INTERNA -->
          <div class="space-y-3">
            <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 pb-1">3. Mantenimiento e Inspección Interna</h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
              <div v-for="(item, idx) in checklistInterna" :key="'int-'+idx" class="p-3 bg-gray-50 dark:bg-gray-700/50 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
                <div class="w-full md:w-1/3 font-semibold text-gray-700 dark:text-gray-200">{{ item.nombre }}</div>
                <div class="flex items-center gap-4 text-gray-700 dark:text-gray-300">
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'int-'+idx" value="SI" v-model="item.estado" class="text-blue-600 dark:bg-gray-700" /> SI</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'int-'+idx" value="NO" v-model="item.estado" class="text-red-600 dark:bg-gray-700" /> NO</label>
                  <label class="flex items-center gap-1 cursor-pointer"><input type="radio" :name="'int-'+idx" value="N/A" v-model="item.estado" class="text-gray-500 dark:bg-gray-700" /> N/A</label>
                </div>
                <input v-model="item.comentario" type="text" placeholder="Comentarios u observaciones..." class="w-full md:w-1/2 text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg px-2 py-1" />
              </div>
            </div>
          </div>

          <!-- 4. PUNTOS DE CHEQUEO DESPUÉS DEL MANTENIMIENTO -->
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800/40 space-y-3">
            <h3 class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase tracking-wider">4. Puntos de Chequeo Después del Mantenimiento</h3>
            <div class="space-y-2">
              <div v-for="(item, idx) in checklistPost" :key="'post-'+idx" class="flex flex-col sm:flex-row sm:items-center justify-between bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-gray-700 text-xs gap-2">
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ item.nombre }}</span>
                <div class="flex items-center gap-3 whitespace-nowrap">
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'post-'+idx" value="SI" v-model="item.estado" class="text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" />
                    <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">VERIFICADO Y OPERATIVO</span>
                  </label>
                  <label class="inline-flex items-center gap-1 cursor-pointer">
                    <input type="radio" :name="'post-'+idx" value="NO" v-model="item.estado" class="text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600" />
                    <span class="text-xs text-red-600 dark:text-red-400 font-semibold">PENDIENTE</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- SECCIÓN 4: OBSERVACIONES Y RECOMENDACIONES -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 space-y-4">
          <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-2 flex items-center gap-2">
            <span>📝</span> Diagnóstico y Trabajo Ejecutado
          </h2>
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Revisión previa del equipo antes del mantenimiento</label>
              <textarea v-model="form.recomendaciones" rows="3" placeholder="Condición del equipo al iniciar la atención..." class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl"></textarea>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Observaciones Generales</label>
              <textarea v-model="form.observaciones" rows="3" placeholder="Notas adicionales o comentarios generales..." class="w-full text-xs border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl"></textarea>
            </div>
          </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="flex justify-end gap-3 pt-2">
          <Link :href="route('mantenimientos.index')" class="px-5 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            Cancelar
          </Link>
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
