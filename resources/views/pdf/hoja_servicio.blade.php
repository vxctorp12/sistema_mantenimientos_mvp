@php
    $tipo = strtoupper($mantenimiento->equipo->tipo_equipo ?? '');
@endphp

@if($tipo === 'IMPRESORA')
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

