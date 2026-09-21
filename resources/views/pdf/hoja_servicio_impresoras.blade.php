<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @php
        if (empty($tituloDocumento)) {
            $codigo = $mantenimiento->equipo->codigo_inventario ?? $mantenimiento->equipo->numero_serie ?? ('ID-'.$mantenimiento->id);
            $codigoLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', trim($codigo));
            $fechaLimpia = $mantenimiento->fecha_mantenimiento ? date('Ymd', strtotime($mantenimiento->fecha_mantenimiento)) : date('Ymd');
            $tituloDocumento = "Reporte_Mantenimiento_{$codigoLimpio}_{$fechaLimpia}";
        }
        if (empty($logoBase64)) {
            $logoPath = public_path('images/logo-rilaz.png');
            if (file_exists($logoPath) && filesize($logoPath) > 0) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            } else {
                try {
                    $ch = curl_init('https://rilaz.com.sv/wp-content/uploads/2026/02/Logo2021-2.png');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    $imageData = curl_exec($ch);
                    curl_close($ch);

                    if ($imageData) {
                        if (!file_exists(public_path('images'))) {
                            @mkdir(public_path('images'), 0755, true);
                        }
                        @file_put_contents($logoPath, $imageData);
                        $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
                    } else {
                        $logoBase64 = '';
                    }
                } catch (\Throwable $e) {
                    $logoBase64 = '';
                }
            }
        }

        if (empty($watermarkBase64)) {
            $wPath = public_path('images/logo-watermark-icon.png');
            if (file_exists($wPath) && filesize($wPath) > 0) {
                $watermarkBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($wPath));
            } else {
                $watermarkBase64 = $logoBase64 ?? '';
            }
        }
    @endphp
    <title>{{ $tituloDocumento }}</title>
    <script>
        document.title = @json($tituloDocumento);
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            position: relative;
        }
        .container {
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        /* MARCA DE AGUA CENTRADA RECTA Y DE GRAN TAMAÑO (SOLO SÍMBOLO SIN TEXTO) */
        .watermark {
            position: absolute;
            top: 16%;
            left: 50%;
            transform: translateX(-50%);
            -webkit-transform: translateX(-50%);
            width: 728px;
            opacity: 0.15;
            z-index: -1000;
            text-align: center;
            pointer-events: none;
        }
        .watermark img {
            width: 728px;
            height: auto;
            max-width: 728px;
        }
        
        /* HEADER DOCUMENTO */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #1e293b;
            background-color: transparent;
        }
        
        /* BANNERS Y ENCABEZADOS DE BLOQUE CON FONDOS SÓLIDOS Y ALTO CONTRASTE */
        .banner-main,
        th.banner-main,
        td.banner-main,
        tr.banner-main th,
        tr.banner-main td {
            background-color: #1e40af !important; /* Azul Corporativo RILAZ */
            color: #ffffff !important;
            font-weight: 800;
            font-size: 9.5px;
            text-transform: uppercase;
            text-align: center;
            padding: 4.5px 6px;
            letter-spacing: 0.6px;
        }

        .subbanner-blue,
        th.subbanner-blue,
        td.subbanner-blue,
        tr.subbanner-blue th,
        tr.subbanner-blue td {
            background-color: #1e40af !important; /* Azul Corporativo RILAZ */
            color: #ffffff !important;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 4px 6px;
        }

        .subbanner-gray,
        th.subbanner-gray,
        td.subbanner-gray,
        tr.subbanner-gray th,
        tr.subbanner-gray td {
            background-color: #475569 !important; /* Gris Slate Secundario */
            color: #ffffff !important;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            padding: 4px 6px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            border: 1px solid #1e293b;
            background-color: transparent;
        }
        .data-table th {
            background-color: #1e40af !important;
            color: #ffffff !important;
            border: 1px solid #1e293b;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 8.5px;
        }
        .data-table td {
            border: 1px solid #64748b;
            padding: 3px 6px;
            vertical-align: middle;
            font-size: 8.5px;
            background-color: transparent !important;
            color: #0f172a;
        }
        .data-table tr:nth-child(even) td {
            background-color: transparent !important;
        }
        
        .checkbox-box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1.5px solid #0f172a;
            text-align: center;
            line-height: 10px;
            font-weight: bold;
            font-size: 8.5px;
            border-radius: 1px;
        }
        .circle-icon {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #0f172a;
            border-radius: 50%;
            text-align: center;
            line-height: 9px;
            font-size: 7.5px;
            font-weight: bold;
            margin-right: 3px;
            vertical-align: middle;
        }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-blue { color: #1e293b; }
        
        /* SIGNATURES MARCO INTEGRADO */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1.5px solid #1e3a8a;
            background-color: transparent;
        }
        .signatures-table td {
            width: 50%;
            height: 52px;
            border: 1.5px solid #1e3a8a;
            vertical-align: bottom;
            text-align: center;
            padding-bottom: 8px;
            font-size: 9px;
            color: #0f172a;
            background-color: transparent !important;
        }
        
        @media print {
            body { margin: 0; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- MARCA DE AGUA EN FONDO (SOLO SÍMBOLO SIN TEXTO, ORIENTACIÓN RECTA Y GRAN TAMAÑO) -->
    <div class="watermark">
        <img src="{{ $watermarkBase64 }}" alt="RILAZ Watermark" />
    </div>

    <!-- ENCABEZADO CON LOGO Y TÍTULO CENTRADO -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                <img src="{{ $logoBase64 }}" alt="RILAZ Logo" style="max-height: 50px; max-width: 150px;" />
            </td>
            <td style="width: 50%; text-align: center; vertical-align: middle;">
                <div style="font-weight: 900; font-size: 13.5px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.8px; line-height: 1.2;">
                    REPORTE DE ASISTENCIA TÉCNICA
                </div>
                <div style="font-weight: 800; font-size: 11px; color: #1e40af; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px;">
                    IMPRESORAS
                </div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                <!-- Sin folio -->
            </td>
        </tr>
    </table>

    <!-- BLOQUE 1: INFORMACIÓN GENERAL -->
    <table class="data-table">
        <thead>
            <tr class="banner-main">
                <th colspan="2" class="banner-main">INFORMACIÓN GENERAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 50%;"><span class="font-bold text-blue">FECHA:</span> {{ $mantenimiento->fecha_mantenimiento ? date('d/m/Y', strtotime($mantenimiento->fecha_mantenimiento)) : date('d/m/Y') }}</td>
                <td style="width: 50%;"><span class="font-bold text-blue">TÉCNICO ENCARGADO:</span> {{ $mantenimiento->tecnico->name ?? $mantenimiento->tecnico->nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="font-bold text-blue">NOMBRE DE CLIENTE:</span> {{ $mantenimiento->contrato->cliente->nombre_cliente ?? 'N/A' }}</td>
                <td style="width: 50%;"><span class="font-bold text-blue">NOMBRE DEL CONTACTO:</span> {{ $mantenimiento->contrato->cliente->contacto_nombre ?? $mantenimiento->equipo->usuario_asignado ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- BLOQUE 2: INFORMACIÓN DEL USUARIO -->
    <table class="data-table">
        <thead>
            <tr class="subbanner-blue">
                <th colspan="2" class="subbanner-blue" style="text-align: center;">INFORMACIÓN DEL USUARIO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 50%;"><span class="font-bold text-blue">NOMBRE DEL USUARIO:</span> {{ $mantenimiento->equipo->usuario_asignado ?? 'N/A' }}</td>
                <td style="width: 50%;"><span class="font-bold text-blue">UNIDAD O DEPARTAMENTO:</span> {{ $mantenimiento->equipo->ubicacion_especifica ?? $mantenimiento->equipo->departamento_unidad ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- INFORMACIÓN DEL EQUIPO IMPRESORA -->
    <table class="data-table">
        <thead>
            <tr class="banner-main">
                <th colspan="4" class="banner-main">INFORMACIÓN DEL EQUIPO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 50%;">
                    <span class="font-bold text-blue">TIPO:</span> &nbsp;&nbsp;
                    <span class="checkbox-box">{{ (str_contains(strtoupper($mantenimiento->equipo->modelo ?? ''), 'LASER') || str_contains(strtoupper($mantenimiento->equipo->marca ?? ''), 'HP')) ? 'X' : 'X' }}</span> Láser &nbsp;&nbsp;
                    <span class="checkbox-box"></span> Matricial &nbsp;&nbsp;
                    <span class="checkbox-box"></span> Inyección de tinta
                </td>
                <td colspan="3" style="width: 50%;">
                    <span class="font-bold text-blue">SERIE / ACTIVO:</span> {{ $mantenimiento->equipo->numero_serie ?? 'N/A' }} &nbsp;/&nbsp; {{ $mantenimiento->equipo->codigo_inventario ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="font-bold text-blue">MARCA:</span> {{ $mantenimiento->equipo->marca ?? 'N/A' }}</td>
                <td colspan="3" style="width: 50%;"><span class="font-bold text-blue">MODELO:</span> {{ $mantenimiento->equipo->modelo ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="font-bold text-blue">DIRECCIÓN IP:</span> {{ $mantenimiento->direccion_ip ?? $mantenimiento->equipo->direccion_ip ?? '' }}</td>
                <td colspan="3" style="width: 50%;"><span class="font-bold text-blue">UBICACIÓN / UNIDAD:</span> {{ $mantenimiento->equipo->ubicacion_especifica ?? $mantenimiento->equipo->departamento_unidad ?? 'Sede Principal' }}</td>
            </tr>
            <tr>
                <td style="width: 50%;"><span class="font-bold text-blue">CONTADOR B/N:</span> {{ $mantenimiento->contador_bn !== null ? number_format($mantenimiento->contador_bn) : '' }}</td>
                <td colspan="3" style="width: 50%;"><span class="font-bold text-blue">CONTADOR COLOR:</span> {{ $mantenimiento->contador_color !== null ? number_format($mantenimiento->contador_color) : '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- CHECK LIST PARA MANTENIMIENTO DE IMPRESORAS -->
    <table class="data-table">
        <thead>
            <tr class="banner-main">
                <th colspan="3" class="banner-main">CHECK LIST PARA MANTENIMIENTO DE IMPRESORAS</th>
            </tr>
            <tr class="subbanner-blue">
                <th class="subbanner-blue" style="width: 85%; text-align: left;">PUNTOS DE CHEQUEO PREVIO</th>
                <th class="subbanner-blue" colspan="2" style="width: 15%; text-align: center;">EJECUCIÓN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="circle-icon">¡</span> Verificar condiciones físicas: manchas, rayones, golpes, cables dañados, fugas de tóner/tinta y estado general.</td>
                <td colspan="2" class="text-center"><span class="checkbox-box">X</span></td>
            </tr>
            <tr>
                <td><span class="circle-icon">¡</span> Encender el equipo y comprobar panel, alimentación eléctrica, conectividad y funciones principales.</td>
                <td colspan="2" class="text-center"><span class="checkbox-box">X</span></td>
            </tr>
            <tr>
                <td><span class="circle-icon">¡</span> Realizar prueba inicial de impresión/copia/escaneo y registrar contadores antes del mantenimiento.</td>
                <td colspan="2" class="text-center"><span class="checkbox-box">X</span></td>
            </tr>
            <tr class="subbanner-blue">
                <th class="subbanner-blue" colspan="3" style="text-align: left;">REVISIÓN PREVIA DEL EQUIPO ANTES DEL MANTENIMIENTO</th>
            </tr>
            <tr>
                <td colspan="3" style="height: 16px;">
                    {{ $mantenimiento->diagnostico ?? $mantenimiento->recomendaciones ?? 'Impresora ingresa para mantenimiento preventivo programado.' }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- MATRIZ DE LIMPIEZA Y MANTENIMIENTO INTERNO DE IMPRESORAS -->
    @php
        $items = ($mantenimiento->detalles && $mantenimiento->detalles->count()) ? $mantenimiento->detalles : ($mantenimiento->checklists ?? []);
        $itemsMap = [];
        foreach ($items as $it) {
            $key = strtoupper(trim($it->punto_chequeo ?? $it->item_verificacion ?? ''));
            $itemsMap[$key] = [
                'estado' => strtoupper($it->estado ?? ($it->realizado ? 'SI' : 'NO')),
                'notas' => $it->observacion ?? $it->comentario ?? $it->comentarios ?? ''
            ];
        }

        $limpiezaExternaImpresoras = [
            'CUBIERTAS Y CARCASA',
            'PANEL DE CONTROL / PANTALLA',
            'BANDEJAS DE PAPEL Y BYPASS',
            'ADF / CRISTAL / ÁREA DE ESCANEO',
            'CABLES Y CONECTORES',
            'OTROS (ESPECIFIQUE)'
        ];

        $mantenimientoInternoImpresoras = [
            'CONSUMIBLES EN BUEN ESTADO',
            'REVISIÓN / LIMPIEZA DE RODILLOS',
            'ESTADO DE FUSOR / CABEZAL',
            'FAJA / UNIDAD DE IMAGEN',
            'LUBRICACIÓN MECÁNICA',
            'LIMPIEZA INTERNA GENERAL',
            'IMPRESIÓN DESDE EL EQUIPO',
            'IMPRESIÓN DESDE COMPUTADORA'
        ];
    @endphp

    <table class="data-table">
        <thead>
            <tr class="subbanner-gray">
                <th class="subbanner-gray" style="width: 40%; text-align: left;">LIMPIEZA EXTERNA</th>
                <th class="subbanner-gray" style="width: 7%; text-align: center;">SI</th>
                <th class="subbanner-gray" style="width: 7%; text-align: center;">NO</th>
                <th class="subbanner-gray" style="width: 46%; text-align: left;">COMENTARIOS:</th>
            </tr>
        </thead>
        <tbody>
            @foreach($limpiezaExternaImpresoras as $row)
                @php
                    $match = null;
                    foreach ($itemsMap as $k => $v) {
                        if (str_contains($k, explode(' ', $row)[0])) { $match = $v; break; }
                    }
                    $st = $match['estado'] ?? 'SI';
                    $nt = $match['notas'] ?? '';
                @endphp
                <tr>
                    <td>{{ $row }}</td>
                    <td class="text-center"><span class="checkbox-box">{{ $st === 'SI' ? 'X' : '' }}</span></td>
                    <td class="text-center"><span class="checkbox-box">{{ $st === 'NO' ? 'X' : '' }}</span></td>
                    <td>{{ $nt }}</td>
                </tr>
            @endforeach
            <tr class="subbanner-gray">
                <th class="subbanner-gray" style="text-align: left;">MANTENIMIENTO INTERNO</th>
                <th class="subbanner-gray" style="text-align: center;">SI</th>
                <th class="subbanner-gray" style="text-align: center;">NO</th>
                <th class="subbanner-gray" style="text-align: left;">COMENTARIOS:</th>
            </tr>
            @foreach($mantenimientoInternoImpresoras as $row)
                @php
                    $match = null;
                    foreach ($itemsMap as $k => $v) {
                        if (str_contains($k, explode(' ', $row)[0])) { $match = $v; break; }
                    }
                    $st = $match['estado'] ?? 'SI';
                    $nt = $match['notas'] ?? '';
                @endphp
                <tr>
                    <td>{{ $row }}</td>
                    <td class="text-center"><span class="checkbox-box">{{ $st === 'SI' ? 'X' : '' }}</span></td>
                    <td class="text-center"><span class="checkbox-box">{{ $st === 'NO' ? 'X' : '' }}</span></td>
                    <td>{{ $nt }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- CHEQUEO POSTERIOR Y RECOMENDACIONES -->
    <table class="data-table">
        <thead>
            <tr class="subbanner-blue">
                <th class="subbanner-blue" style="width: 85%; text-align: left;">PUNTOS DE CHEQUEO DESPUÉS DEL MANTENIMIENTO</th>
                <th class="subbanner-blue" style="width: 15%; text-align: center;">EJECUCIÓN</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="circle-icon">¡</span> Verificar funcionamiento mediante pruebas de impresión, copia/escaneo, conectividad y calidad de salida.</td>
                <td class="text-center"><span class="checkbox-box">X</span></td>
            </tr>
            <tr class="subbanner-blue">
                <th class="subbanner-blue" colspan="2" style="text-align: left;">OBSERVACIONES GENERALES / RECOMENDACIONES / REPUESTOS REQUERIDOS</th>
            </tr>
            <tr>
                <td colspan="2" style="height: 24px; vertical-align: top;">
                    {{ $mantenimiento->observaciones ?? $mantenimiento->trabajo_realizado ?? 'Mantenimiento preventivo ejecutado satisfactoriamente. Impresora limpia, calibrada y lista para uso.' }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- FIRMAS DE CONFORMIDAD -->
    <table class="signatures-table">
        <tr>
            <td style="width: 50%; border-right: 1.5px solid #1e3a8a;">
                <br><br>
                _______________________________________<br>
                <strong style="font-size: 9px; color: #0f172a;">Nombre, firma y sello de cliente</strong>
            </td>
            <td style="width: 50%;">
                <br><br>
                _______________________________________<br>
                <strong style="font-size: 9px; color: #0f172a;">Firma de técnico</strong>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
