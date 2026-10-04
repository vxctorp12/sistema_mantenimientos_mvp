<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Configuración de la página en horizontal */
        @page {
            size: letter landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Contenedor principal para cada formulario para evitar saltos de página a la mitad */
        .form-container {
            width: 100%;
            page-break-inside: avoid;
            margin-bottom: 20px; /* Separación si hay 2 por página */
        }

        /* Tabla principal del formulario */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 120px;
            text-align: center;
        }

        .logo-cell img {
            max-width: 100px;
            max-height: 40px;
        }

        .header-label {
            font-weight: bold;
            font-size: 10px;
        }

        .chk-col {
            width: 30px;
            text-align: center;
        }

        .desc-col {
            width: 50%;
        }

        .checkbox-img {
            width: 12px;
            height: 12px;
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }
        
        .observaciones-box {
            height: 50px;
            vertical-align: top;
        }

        .section-title {
            font-weight: bold;
            background-color: #f0f0f0; /* Un tono sutil si lo desean, el original es blanco */
        }
    </style>
</head>
<body>

@php
    // Si pasamos un array de mantenimientos (batch), lo usamos, si no, creamos un array con el único mantenimiento
    $listaMantenimientos = isset($mantenimientos) ? $mantenimientos : (isset($mantenimiento) ? [$mantenimiento] : []);

    // Si está vacío (para pruebas de diseño puro), creamos arreglos de prueba
    if (empty($listaMantenimientos)) {
        $listaMantenimientos = [
            (object)[
                'id' => 1,
                'fecha_mantenimiento' => '28/09/2026',
                'equipo' => (object)[
                    'tipo_equipo' => 'ESCRITORIO',
                    'departamento_unidad' => 'ACCESO A LA INFORMACION PUBLICA',
                    'codigo_inventario' => '62768',
                    'usuario_asignado' => 'FLOR ALICIA DERAS'
                ],
                'tecnico' => (object)['name' => 'DANIEL RODRIGUEZ'],
                'observaciones' => 'EQUIPO EN BUEN ESTADO'
            ],
            (object)[
                'id' => 2,
                'fecha_mantenimiento' => '24/09/2026',
                'equipo' => (object)[
                    'tipo_equipo' => 'ESCÁNER',
                    'departamento_unidad' => 'ADJUNTA PARA ASUNTOS INTERNACIONALES',
                    'codigo_inventario' => '76866',
                    'usuario_asignado' => 'GREDIS ELIZABETH SANTOS CARRANZA'
                ],
                'tecnico' => (object)['name' => 'EDWIN GONZALEZ'],
                'observaciones' => 'EQUIPO EN BUEN ESTADO'
            ]
        ];
    }
@endphp

@foreach($listaMantenimientos as $index => $mtto)
    @php
        $tipo = strtoupper($mtto->equipo->tipo_equipo ?? 'LAPTOP');
        
        // Determinar las tareas en base al tipo de equipo
        $tareas = [];
        if (in_array($tipo, ['DESKTOP', 'ESCRITORIO'])) {
            $tipoLabel = 'ESCRITORIO';
            $tareas = [
                'Limpieza general externa del equipo (incluye todos los dispositivos externos, monitor, teclado y mouse y demás dispositivos).',
                'Limpieza interna de todos los dispositivos del CPU.',
                'Lubricación y limpieza de ventiladores del chasis y microprocesador.',
                'Verificación del uso adecuado de la memoria (administrador de tareas).',
                'Chequeo y cambio interno de baterías CMOS.',
                'Chequeo de voltaje de la fuente y aspirado.',
                'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.',
                'Versión de Windows y Office:',
            ];
        } elseif ($tipo == 'LAPTOP') {
            $tipoLabel = 'LAPTOP';
            $tareas = [
                'Limpieza general externa del equipo (incluyendo todos los accesorios externos).',
                'Limpieza del ventilador de salida de aire',
                'Limpieza del teclado y el touch pad.',
                'Limpieza de lente de CD ROM ó DVD+-RW',
                'Limpieza de los puertos de conectividad.',
                'Limpieza de la pantalla de cristal líquido.',
                'Chequeo de voltaje de la fuente de alimentación.',
                'Realizar Diagnósticos al estado de la batería interna de la laptop.',
                'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.',
                'Versión de Windows y Office:',
            ];
        } elseif (in_array($tipo, ['ESCÁNER', 'ESCANER'])) {
            $tipoLabel = 'ESCÁNER';
            $tareas = [
                'Limpieza externa del escáner',
                'Limpieza del cristal',
                'Limpieza del alimentador',
                'Revisión de rodillos',
                'Revisión de sensores',
                'Revisión de cables',
                'Prueba de digitalización'
            ];
        } else {
            $tipoLabel = $tipo;
            $tareas = [
                'Limpieza general externa del equipo.',
                'Revisión de funcionamiento general.',
                'Comprobar que el equipo queda funcionando a entera satisfacción del usuario.'
            ];
        }

        // Obtener la url del logo
        $logoPath = public_path('images/logo-rilaz.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <div class="form-container">
        <table>
            <!-- ENCABEZADO -->
            <tr>
                <td rowspan="2" class="logo-cell text-center">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="RILAZ">
                    @else
                        <strong>RILAZ</strong>
                    @endif
                </td>
                <td colspan="2">Rilaz S.A. de C.V.</td>
                <td><span class="header-label">Fecha:</span></td>
                <td>{{ $mtto->fecha_mantenimiento ?? date('d/m/Y') }}</td>
                <td><span class="header-label">Tipo de Equipo:</span></td>
                <td><strong>{{ $tipoLabel }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><span class="header-label">Unidad:</span> {{ $mtto->equipo->departamento_unidad ?? '' }}</td>
                <td colspan="2"><span class="header-label">Oficina:</span> COMPLEJO FGR</td>
                <td><span class="header-label">Formulario:</span></td>
                <td>{{ $mtto->id ?? ($index + 1) }}</td>
            </tr>
            <tr>
                <td colspan="3"><span class="header-label">No. Correlativo</span></td>
                <td colspan="2"><span class="header-label">Activo Fijo:</span></td>
                <td colspan="2">{{ $mtto->equipo->codigo_inventario ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="3"><span class="header-label">Nombre Completo de Usuario:</span> {{ $mtto->equipo->usuario_asignado ?? '' }}</td>
                <td colspan="4"><span class="header-label">Técnico:</span> {{ $mtto->tecnico->name ?? $mtto->tecnico->nombre ?? 'DANIEL RODRIGUEZ' }}</td>
            </tr>
            <tr style="height: 22px;">
                <td colspan="3"><span class="header-label">Post-Mantenimiento</span></td>
                <td colspan="4"><span class="header-label">Pre-Mantenimiento</span></td>
            </tr>

            <!-- CABECERA DEL CHECKLIST -->
            <tr>
                <td class="text-center section-title"><strong>Chk</strong></td>
                <td colspan="4" class="text-center section-title desc-col"><strong>Descripción</strong></td>
                <td colspan="2" class="text-center section-title"><strong>Recomendaciones</strong></td>
            </tr>

            <!-- TAREAS DEL CHECKLIST -->
            @foreach($tareas as $tarea)
                @php
                    // Encontrar el item correspondiente en la BD
                    $checkItem = $mtto->checklists->first(function($item) use ($tarea) {
                        // Buscar coincidencia ignorando espacios y mayúsculas/minúsculas
                        return strtolower(trim($item->item_verificacion)) === strtolower(trim($tarea));
                    });
                    
                    $isRealizado = $checkItem ? ($checkItem->estado === 'SI') : false;
                    $recomendacion = $checkItem ? $checkItem->comentario : '';
                @endphp
                <tr>
                    <td class="text-center">
                        @if($isRealizado)
                            <div style="width:14px; height:14px; background-color:#1e90ff; color:white; display:inline-block; border-radius:2px; font-weight:bold; font-size:12px; line-height:14px; text-align:center;">✓</div>
                        @else
                            <div style="width:14px; height:14px; background-color:#fff; display:inline-block; border-radius:2px; border: 1px solid #000;"></div>
                        @endif
                    </td>
                    <td colspan="4">{{ $tarea }}</td>
                    <td colspan="2" style="font-size: 10px;">{{ $recomendacion }}</td>
                </tr>
            @endforeach

            <!-- OBSERVACIONES -->
            <tr>
                <td colspan="2" style="font-weight: bold; vertical-align: middle;">Observaciones</td>
                <td colspan="5" class="observaciones-box">
                    {{ $mtto->observaciones ?? '' }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Forzamos un salto de página cada 2 formularios si estamos imprimiendo en lote --}}
    @if(count($listaMantenimientos) > 1 && ($index + 1) % 2 == 0 && !$loop->last)
        <div style="page-break-after: always;"></div>
    @endif

@endforeach

</body>
<script>
    window.onload = function() {
        window.print();
    };
</script>
</html>
