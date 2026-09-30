<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Servicio - Mantenimiento v2</title>
    <style>
        /* Configuración de la página en horizontal */
        @page {
            size: letter landscape;
            margin: 0; /* Oculta encabezados y pie de página del navegador (URL, Título) */
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 10mm; /* Mueve el margen a padding del body para no cortar contenido */
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

        .text-center {
            text-align: center;
        }
        
        .observaciones-box {
            height: 50px;
            vertical-align: top;
        }

        .section-title {
            font-weight: bold;
            background-color: #f0f0f0; 
        }
    </style>
</head>
<body>

@php
    // Si pasamos un array de mantenimientos (batch), lo usamos, si no, creamos un array con el único mantenimiento
    $listaMantenimientos = isset($mantenimientos) ? $mantenimientos : (isset($mantenimiento) ? [$mantenimiento] : []);
@endphp

@foreach($listaMantenimientos as $index => $mtto)
    @php
        // Obtenemos los ítems de verificación dinámicamente desde la base de datos
        // De esta forma no necesitamos un archivo por equipo ni hardcodear las listas
        $items = ($mtto->detalles && $mtto->detalles->count()) ? $mtto->detalles : ($mtto->checklists ?? []);

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
                <td>{{ $mtto->fecha_mantenimiento ? date('d/m/Y', strtotime($mtto->fecha_mantenimiento)) : date('d/m/Y') }}</td>
                <td><span class="header-label">Tipo de Equipo:</span></td>
                <td><strong>{{ strtoupper($mtto->equipo->tipo_equipo ?? '') }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><span class="header-label">Unidad:</span> {{ $mtto->equipo->departamento_unidad ?? '' }}</td>
                <td colspan="2"><span class="header-label">Oficina:</span> COMPLEJO FGR</td>
                <td><span class="header-label">Formulario:</span></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="3"><span class="header-label">No. Correlativo</span></td>
                <td colspan="2"><span class="header-label">Activo Fijo:</span></td>
                <td colspan="2">{{ $mtto->equipo->codigo_inventario ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="3"><span class="header-label">Nombre Completo de Usuario:</span> {{ $mtto->equipo->usuario_asignado ?? '' }}</td>
                <td colspan="4"><span class="header-label">Técnico:</span> {{ $mtto->tecnico->name ?? $mtto->tecnico->nombre ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="3"><span class="header-label">Post-Mantenimiento</span></td>
                <td colspan="4"><span class="header-label">Pre-Mantenimiento</span></td>
            </tr>

            <!-- CABECERA DEL CHECKLIST -->
            <tr>
                <td class="text-center section-title"><strong>Chk</strong></td>
                <td colspan="4" class="text-center section-title desc-col"><strong>Descripción</strong></td>
                <td colspan="2" class="text-center section-title"><strong>Recomendaciones</strong></td>
            </tr>

            <!-- TAREAS DEL CHECKLIST (Dinámicas desde DB) -->
            @forelse($items as $item)
                @php
                    $isMarcado = (isset($item->estado) && strtoupper($item->estado) === 'SI') || (!isset($item->estado) && !empty($item->realizado));
                    $descripcion = $item->punto_chequeo ?? $item->item_verificacion ?? $item->tarea ?? '';
                    $recomendacion = $item->observacion ?? $item->comentario ?? $item->comentarios ?? '';
                @endphp
                <tr>
                    <td class="text-center">
                        @if($isMarcado)
                            <div style="width:14px; height:14px; background-color:#1e90ff; color:white; display:inline-block; border-radius:2px; font-weight:bold; font-size:12px; line-height:14px;">✓</div>
                        @else
                            <div style="width:14px; height:14px; border:1px solid #000; display:inline-block; border-radius:2px;"></div>
                        @endif
                    </td>
                    <td colspan="4">{{ $descripcion }}</td>
                    <td colspan="2">{{ $recomendacion }}</td>
                </tr>
            @empty
                <!-- Filas vacías por defecto si no hay detalles en BD aún -->
                @for($i=0; $i<8; $i++)
                <tr>
                    <td class="text-center">
                        <div style="width:14px; height:14px; border:1px solid #000; display:inline-block; border-radius:2px;"></div>
                    </td>
                    <td colspan="4"></td>
                    <td colspan="2"></td>
                </tr>
                @endfor
            @endforelse

            <!-- OBSERVACIONES -->
            <tr>
                <td colspan="2" style="font-weight: bold; vertical-align: middle;">Observaciones</td>
                <td colspan="5" class="observaciones-box">
                    {{ $mtto->observaciones ?? $mtto->trabajo_realizado ?? '' }}
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
