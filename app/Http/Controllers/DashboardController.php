<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Muestra el panel principal de métricas de avance del contrato activo.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Obtener lista de contratos activos para el selector (filtrado si es técnico)
        $query = Contrato::with('cliente')->where('estado', 'ACTIVO');

        if ($user && $user->rol === 'TECNICO') {
            $query->whereHas('tecnicos', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($user && $user->rol === 'INVITADO') {
            $query->where('cliente_id', $user->cliente_id);
        }

        $contratosRaw = $query->get();

        $contratosActivos = $contratosRaw->map(function ($c) {
            $metaTotal = ($c->meta_equipos_total ?? 0) * ($c->mantenimientos_por_equipo ?? 1);
            $realizados = Mantenimiento::where('contrato_id', $c->id)->count();
            $porcentaje = $metaTotal > 0 ? round(($realizados / $metaTotal) * 100, 1) : 0;
            return [
                'id' => $c->id,
                'cliente_id' => $c->cliente_id,
                'cliente' => $c->cliente,
                'nombre_cliente' => $c->cliente->nombre_cliente ?? $c->cliente->nombre_empresa ?? 'Cliente',
                'ubicacion_general' => $c->ubicacion_general ?? 'Sede Principal',
                'meta_equipos_total' => $c->meta_equipos_total,
                'mantenimientos_por_equipo' => $c->mantenimientos_por_equipo,
                'meta_mantenimientos_total' => $metaTotal,
                'total_realizados' => $realizados,
                'porcentaje_avance' => $porcentaje,
                'estado' => $c->estado,
            ];
        });

        // 2. Seleccionar el contrato solicitado o el primero activo por defecto si aplica
        $contratoId = $request->input('contrato_id');

        if ($contratoId) {
            $contrato = $contratosRaw->firstWhere('id', $contratoId);
        } else {
            // Si el técnico tiene más de 1 contrato y no especificó contrato_id, se muestra la grilla de selección de contratos sin auto-seleccionar
            if ($user && $user->rol === 'TECNICO' && $contratosRaw->count() > 1) {
                $contrato = null;
            } else {
                $contrato = $contratosRaw->first();
            }
        }

        if (!$contrato) {
            return Inertia::render('Dashboard', [
                'contratos' => $contratosActivos,
                'contratoSeleccionado' => null,
                'contratoActual' => null,
                'metricas' => null
            ]);
        }

        // Cargar metas desglosadas por tipo si existen
        $contrato->load('metasTipo');

        // 3. Cálculos de Metas y Avance Global
        $metaEquiposTotal = $contrato->meta_equipos_total ?? 0;
        $frecuenciaServicio = $contrato->mantenimientos_por_equipo ?? 1;
        $metaMantenimientosTotal = $metaEquiposTotal * $frecuenciaServicio;

        // Total de mantenimientos realizados en este contrato
        $totalRealizados = Mantenimiento::where('contrato_id', $contrato->id)->count();
        $misMantenimientosRealizados = $user ? Mantenimiento::where('contrato_id', $contrato->id)->where('tecnico_id', $user->id)->count() : 0;

        $porcentajeAvanceGlobal = $metaMantenimientosTotal > 0 
            ? round(($totalRealizados / $metaMantenimientosTotal) * 100, 1) 
            : 0;

        // 4. Métricas de Impresión y Control Operativo
        $totalImpresos = Mantenimiento::where('contrato_id', $contrato->id)
            ->where('impreso', 1)
            ->count();
            
        $pendientesImpresion = $totalRealizados - $totalImpresos;

        // 5. Equipos Únicos Atendidos vs Meta
        $equiposUnicosAtendidos = Mantenimiento::where('contrato_id', $contrato->id)
            ->distinct('equipo_id')
            ->count('equipo_id');

        // 6. Desglose de Atenciones por Tipo de Equipo
        $atencionesPorTipoRaw = DB::table('mantenimientos')
            ->join('equipos', 'mantenimientos.equipo_id', '=', 'equipos.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select('equipos.tipo_equipo', DB::raw('count(*) as total'))
            ->groupBy('equipos.tipo_equipo')
            ->pluck('total', 'tipo_equipo')
            ->toArray();

        $tiposDisponibles = ['DESKTOP', 'LAPTOP', 'IMPRESORA', 'OTRO'];
        $desglosePorTipo = [];

        foreach ($tiposDisponibles as $tipo) {
            $metaTipoObj = $contrato->metasTipo->firstWhere('tipo_equipo', $tipo);
            $metaTipoCantidad = $metaTipoObj ? ($metaTipoObj->cantidad_meta * $frecuenciaServicio) : 0;
            $realizadosTipo = $atencionesPorTipoRaw[$tipo] ?? 0;

            $desglosePorTipo[] = [
                'tipo' => $tipo,
                'realizados' => $realizadosTipo,
                'meta' => $metaTipoCantidad,
                'porcentaje' => $metaTipoCantidad > 0 ? round(($realizadosTipo / $metaTipoCantidad) * 100, 1) : 0
            ];
        }

        // 7. Rendimiento y Productividad por Técnico
        $productividadTecnicos = DB::table('mantenimientos')
            ->join('users', 'mantenimientos.tecnico_id', '=', 'users.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select(
                'users.id',
                'users.name as nombre',
                DB::raw('count(mantenimientos.id) as total_atenciones'),
                DB::raw('sum(mantenimientos.impreso) as total_impresos')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_atenciones')
            ->get();

        // 8. Últimos Mantenimientos Registrados (Actividad Reciente)
        $ultimosQuery = Mantenimiento::with(['equipo', 'tecnico'])
            ->where('contrato_id', $contrato->id);

        if ($user && $user->rol === 'TECNICO') {
            $ultimosQuery->where('tecnico_id', $user->id);
        }

        $ultimosMantenimientos = $ultimosQuery->orderByDesc('fecha_mantenimiento')
            ->take(6)
            ->get()
            ->map(function ($mtto) {
                return [
                    'id' => $mtto->id,
                    'fecha' => $mtto->fecha_mantenimiento ? $mtto->fecha_mantenimiento->format('d/m/Y H:i') : 'N/A',
                    'tipo_equipo' => $mtto->equipo->tipo_equipo ?? 'OTRO',
                    'serie' => $mtto->equipo->numero_serie ?? 'S/N',
                    'equipo_serie' => $mtto->equipo->numero_serie ?? 'S/N',
                    'codigo_inventario' => $mtto->equipo->codigo_inventario ?? '',
                    'equipo_inventario' => $mtto->equipo->codigo_inventario ?? 'N/A',
                    'marca' => $mtto->equipo->marca ?? '',
                    'modelo' => $mtto->equipo->modelo ?? '',
                    'marca_modelo' => ($mtto->equipo->marca ?? '') . ' ' . ($mtto->equipo->modelo ?? ''),
                    'tecnico_id' => $mtto->tecnico_id,
                    'tecnico_nombre' => $mtto->tecnico->name ?? $mtto->tecnico->nombre ?? 'Sin asignar',
                    'impreso' => (bool)$mtto->impreso,
                    'estado_firma' => $mtto->estado_firma
                ];
            });

        $contratoInfo = [
            'id' => $contrato->id,
            'cliente_nombre' => $contrato->cliente->nombre_cliente ?? $contrato->cliente->nombre_empresa ?? 'Cliente',
            'ubicacion' => $contrato->ubicacion_general,
            'fecha_inicio' => $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A',
            'fecha_limite' => $contrato->fecha_limite ? $contrato->fecha_limite->format('d/m/Y') : 'Sin fecha límite',
        ];

        return Inertia::render('Dashboard', [
            'contratos' => $contratosActivos,
            'contratoSeleccionado' => $contratoInfo,
            'contratoActual' => $contratoInfo,
            'metricas' => [
                'meta_equipos' => $metaEquiposTotal,
                'frecuencia' => $frecuenciaServicio,
                'meta_mantenimientos' => $metaMantenimientosTotal,
                'meta_mantenimientos_total' => $metaMantenimientosTotal,
                'total_mantenimientos' => $totalRealizados,
                'total_realizados' => $totalRealizados,
                'mis_realizados' => $misMantenimientosRealizados,
                'porcentaje_avance' => $porcentajeAvanceGlobal,
                'porcentaje_avance_global' => $porcentajeAvanceGlobal,
                'equipos_unicos_atendidos' => $equiposUnicosAtendidos,
                'total_impresos' => $totalImpresos,
                'pendientes_impresion' => $pendientesImpresion,
                'desglose_por_tipo' => $desglosePorTipo,
                'productividad_tecnicos' => $productividadTecnicos,
                'ultimos_mantenimientos' => $ultimosMantenimientos
            ]
        ]);
    }

    public function contratoDashboard(Request $request, Contrato $contrato)
    {
        $user = Auth::user();

        if ($user->rol === 'TECNICO') {
            if (!$contrato->tecnicos()->where('users.id', $user->id)->exists()) {
                abort(403, 'No tienes acceso a este contrato.');
            }
        }

        if ($user->rol === 'INVITADO') {
            if ($contrato->cliente_id !== $user->cliente_id) {
                abort(403, 'No tienes acceso a este contrato.');
            }
        }

        $contrato->load('cliente');

        // 1. Mantenimientos diarios
        $mantenimientosDiarios = DB::table('mantenimientos')
            ->where('contrato_id', $contrato->id)
            ->whereNotNull('fecha_mantenimiento')
            ->select(DB::raw('DATE(fecha_mantenimiento) as fecha'), DB::raw('count(*) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha', 'asc')
            ->get();

        // 2. Mantenimientos por Técnico
        $mantenimientosPorTecnico = DB::table('mantenimientos')
            ->join('users', 'mantenimientos.tecnico_id', '=', 'users.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select('users.name as tecnico', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->get();

        // 3. Mantenimientos por Tipo de Equipo
        $mantenimientosPorTipo = DB::table('mantenimientos')
            ->join('equipos', 'mantenimientos.equipo_id', '=', 'equipos.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select(DB::raw('COALESCE(equipos.tipo_equipo, "OTRO") as tipo'), DB::raw('count(*) as total'))
            ->groupBy('tipo')
            ->get();

        // 4. Mantenimientos por Marca
        $mantenimientosPorMarca = DB::table('mantenimientos')
            ->join('equipos', 'mantenimientos.equipo_id', '=', 'equipos.id')
            ->where('mantenimientos.contrato_id', $contrato->id)
            ->select(DB::raw('COALESCE(equipos.marca, "DESCONOCIDA") as marca'), DB::raw('count(*) as total'))
            ->groupBy('marca')
            ->orderByDesc('total')
            ->get();

        // 5. Historial paginado (lista)
        $queryMantenimientos = Mantenimiento::with(['equipo', 'tecnico'])
            ->where('contrato_id', $contrato->id)
            ->orderByDesc('fecha_mantenimiento');

        if ($user->rol === 'TECNICO') {
            $queryMantenimientos->where('tecnico_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $queryMantenimientos->whereHas('equipo', function($q) use ($search) {
                $q->where('numero_serie', 'like', "%{$search}%")
                  ->orWhere('codigo_inventario', 'like', "%{$search}%");
            });
        }

        $mantenimientosPaginados = $queryMantenimientos->paginate(15)->withQueryString();

        $contratoInfo = [
            'id' => $contrato->id,
            'cliente_nombre' => $contrato->cliente->nombre_cliente ?? $contrato->cliente->nombre_empresa ?? 'Cliente',
            'ubicacion' => $contrato->ubicacion_general,
            'fecha_inicio' => $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A',
            'fecha_limite' => $contrato->fecha_limite ? $contrato->fecha_limite->format('d/m/Y') : 'Sin fecha límite',
        ];

        return Inertia::render('Contratos/Dashboard', [
            'contrato' => $contratoInfo,
            'mantenimientosDiarios' => $mantenimientosDiarios,
            'mantenimientosPorTecnico' => $mantenimientosPorTecnico,
            'mantenimientosPorTipo' => $mantenimientosPorTipo,
            'mantenimientosPorMarca' => $mantenimientosPorMarca,
            'mantenimientos' => $mantenimientosPaginados,
            'filters' => $request->only(['search'])
        ]);
    }

    public function contratoDetalles(Request $request, Contrato $contrato)
    {
        $user = Auth::user();

        if ($user->rol === 'TECNICO') {
            if (!$contrato->tecnicos()->where('users.id', $user->id)->exists()) {
                abort(403, 'No tienes acceso a este contrato.');
            }
        }

        if ($user->rol === 'INVITADO') {
            if ($contrato->cliente_id !== $user->cliente_id) {
                abort(403, 'No tienes acceso a este contrato.');
            }
        }

        $contrato->load(['cliente.sedes']);

        $contratoInfo = [
            'id' => $contrato->id,
            'cliente_id' => $contrato->cliente_id,
            'cliente_nombre' => $contrato->cliente->nombre_cliente ?? $contrato->cliente->nombre_empresa ?? 'Cliente',
            'contacto_nombre' => $contrato->cliente->contacto_nombre ?? null,
            'ubicacion' => $contrato->ubicacion_general,
            'fecha_inicio' => $contrato->fecha_inicio ? $contrato->fecha_inicio->format('d/m/Y') : 'N/A',
            'fecha_limite' => $contrato->fecha_limite ? $contrato->fecha_limite->format('d/m/Y') : 'Sin fecha límite',
            'requerimientos_especiales' => $contrato->requerimientos_especiales,
            'sedes' => $contrato->cliente->sedes ?? [],
        ];

        return Inertia::render('Contratos/Detalles', [
            'contrato' => $contratoInfo
        ]);
    }
}
