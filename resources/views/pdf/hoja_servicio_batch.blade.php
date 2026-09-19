<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDocumento ?? ('Reportes_Mantenimiento_Consolidados_' . date('Ymd')) }}</title>
    <script>
        document.title = @json($tituloDocumento ?? ('Reportes_Mantenimiento_Consolidados_' . date('Ymd')));
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page-break {
            page-break-after: always;
            break-after: page;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .logo-cell { width: 20%; vertical-align: middle; }
        .logo-rilaz { display: flex; align-items: center; gap: 6px; }
        .logo-symbol { width: 32px; height: 32px; }
        .logo-text { font-weight: 900; font-size: 16px; color: #991b1b; letter-spacing: 1px; }
        .title-cell { width: 50%; text-align: center; vertical-align: middle; }
        .report-title { font-weight: 800; font-size: 11px; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta-cell { width: 30%; font-size: 8.5px; vertical-align: middle; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 1.5px 3px; }
        .field-label { font-weight: bold; color: #374151; }
        .field-line { border-bottom: 1px solid #6b7280; display: inline-block; width: 95%; min-height: 11px; }

        .banner-blue { background-color: #2563eb !important; color: #ffffff !important; font-weight: bold; font-size: 9.5px; text-transform: uppercase; text-align: center; padding: 3px 6px; letter-spacing: 0.5px; }
        .subbanner-blue { background-color: #3b82f6 !important; color: #ffffff !important; font-weight: bold; font-size: 8.5px; text-transform: uppercase; padding: 2.5px 6px; }
        .subbanner-gray { background-color: #6b7280 !important; color: #ffffff !important; font-weight: bold; font-size: 8.5px; text-transform: uppercase; padding: 2.5px 6px; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; border: 1px solid #4b5563; }
        .data-table td, .data-table th { border: 1px solid #6b7280; padding: 3px 5px; vertical-align: middle; font-size: 8.5px; }
        .checkbox-box { display: inline-block; width: 10px; height: 10px; border: 1px solid #111827; text-align: center; line-height: 9px; font-weight: bold; font-size: 8px; }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .signatures-table { width: 100%; border-collapse: collapse; margin-top: 10px; border: 1px solid #6b7280; }
        .signatures-table td { width: 50%; height: 45px; border: 1px solid #6b7280; vertical-align: bottom; text-align: center; padding-bottom: 5px; font-size: 8.5px; color: #374151; }
        
        @media print { body { margin: 0; } }
    </style>
</head>
<body>

    @foreach($mantenimientos as $index => $mantenimiento)
        <div class="{{ !$loop->last ? 'page-break' : '' }}">
            @if(strtoupper($mantenimiento->equipo->tipo_equipo ?? '') === 'IMPRESORA')
                @include('pdf.hoja_servicio_impresoras', [
                    'mantenimiento' => $mantenimiento, 
                    'tituloDocumento' => $tituloDocumento ?? null,
                    'logoBase64' => $logoBase64 ?? null,
                    'watermarkBase64' => $watermarkBase64 ?? null
                ])
            @else
                @include('pdf.hoja_servicio_computo', [
                    'mantenimiento' => $mantenimiento, 
                    'tituloDocumento' => $tituloDocumento ?? null,
                    'logoBase64' => $logoBase64 ?? null,
                    'watermarkBase64' => $watermarkBase64 ?? null
                ])
            @endif
        </div>
    @endforeach

</body>
</html>
