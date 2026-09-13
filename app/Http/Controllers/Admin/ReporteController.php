<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Exportación de Mantenimientos e Inventario a CSV/Excel delimitado
     */
    public function exportarExcel(Contrato $contrato)
    {
        $fileName = "reporte_mantenimientos_contrato_{$contrato->id}_" . date('Y-m-d') . ".csv";

        $mantenimientos = Mantenimiento::with(['equipo', 'tecnico'])
            ->where('contrato_id', $contrato->id)
            ->get();

        $response = new StreamedResponse(function () use ($mantenimientos) {
            $handle = fopen('php://output', 'w');

            // BOM para compatibilidad con caracteres especiales en Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Encabezados
            fputcsv($handle, [
                'ID Mtto',
                'Fecha Mtto',
                'Código Inventario',
                'Tipo Equipo',
                'Marca',
                'Modelo',
                'Número de Serie',
                'Usuario Responsable',
                'Departamento/Unidad',
                'Técnico Encargado',
                'Impreso/Firmado',
                'Observaciones'
            ], ';');

            foreach ($mantenimientos as $m) {
                fputcsv($handle, [
                    $m->id,
                    $m->fecha_mantenimiento,
                    $m->equipo->codigo_inventario ?? 'N/A',
                    $m->equipo->tipo_equipo ?? 'OTRO',
                    $m->equipo->marca ?? 'N/A',
                    $m->equipo->modelo ?? 'N/A',
                    $m->equipo->numero_serie ?? 'N/A',
                    $m->equipo->usuario_asignado ?? 'N/A',
                    $m->equipo->departamento_unidad ?? 'N/A',
                    $m->tecnico->name ?? 'N/A',
                    $m->impreso ? 'SI' : 'NO',
                    $m->observaciones
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }
}
