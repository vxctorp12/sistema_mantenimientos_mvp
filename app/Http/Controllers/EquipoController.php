<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipoController extends Controller
{
    /**
     * Lista global de inventario de equipos.
     */
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $query = Equipo::withCount('mantenimientos')->latest('actualizado_en');

        if ($user && $user->rol === 'INVITADO') {
            $query->whereHas('mantenimientos.contrato', function ($q) use ($user) {
                $q->where('cliente_id', $user->cliente_id);
            });
        }

        // Filtro para listar equipos pendientes que no han recibido mantenimiento
        if ($request->input('sin_mantenimiento') === '1' || $request->input('filtro') === 'pendientes') {
            $query->doesntHave('mantenimientos');
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_serie', 'like', "%{$search}%")
                  ->orWhere('codigo_inventario', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('usuario_asignado', 'like', "%{$search}%")
                  ->orWhere('departamento_unidad', 'like', "%{$search}%");
            });
        }

        if ($tipo = $request->input('tipo_equipo')) {
            $query->where('tipo_equipo', $tipo);
        }

        $pendientesQuery = Equipo::doesntHave('mantenimientos');
        $totalQuery = Equipo::query();

        if ($user && $user->rol === 'INVITADO') {
            // Un invitado no puede tener equipos sin mantenimiento (porque la relación se da a través del contrato del mantenimiento)
            $pendientesQuery->whereRaw('1 = 0');
            $totalQuery->whereHas('mantenimientos.contrato', function ($q) use ($user) {
                $q->where('cliente_id', $user->cliente_id);
            });
        }

        $pendientesCount = $pendientesQuery->count();
        $totalCount = $totalQuery->count();

        return Inertia::render('Equipos/Index', [
            'equipos' => $query->paginate(24)->withQueryString(),
            'filters' => $request->only(['search', 'tipo_equipo', 'sin_mantenimiento', 'filtro']),
            'pendientesCount' => $pendientesCount,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * API Endpoint para buscar un equipo por número de serie en caliente.
     */
    public function buscarPorSerie($serie)
    {
        $term = trim($serie);
        $equipo = Equipo::where('numero_serie', $term)
            ->orWhere('codigo_inventario', $term)
            ->first();

        if ($equipo) {
            return response()->json([
                'encontrado' => true,
                'equipo'     => $equipo,
            ]);
        }

        return response()->json([
            'encontrado' => false,
            'mensaje'    => 'Equipo no registrado anteriormente.',
        ]);
    }

    /**
     * Buscar equipo por Código de Inventario (Placa/Activo Fijo) o Número de Serie.
     */
    public function buscarPorInventario(Request $request)
    {
        $query = trim($request->input('codigo_inventario') ?? $request->input('query') ?? '');

        if (empty($query)) {
            return response()->json(['encontrado' => false, 'equipo' => null]);
        }

        // Búsqueda prioritaria por Código de Inventario y secundaria por Número de Serie
        $equipo = Equipo::where('codigo_inventario', $query)
            ->orWhere('numero_serie', $query)
            ->first();

        if ($equipo) {
            return response()->json([
                'encontrado' => true,
                'equipo' => [
                    'id'                  => $equipo->id,
                    'codigo_inventario'   => $equipo->codigo_inventario,
                    'numero_serie'        => $equipo->numero_serie,
                    'tipo_equipo'         => $equipo->tipo_equipo,
                    'marca'               => $equipo->marca,
                    'modelo'              => $equipo->modelo,
                    'departamento_unidad' => $equipo->departamento_unidad,
                    'usuario_asignado'    => $equipo->usuario_asignado,
                    'direccion_ip'        => $equipo->direccion_ip ?? '',
                    'sistema_operativo'   => $equipo->sistema_operativo ?? '',
                ]
            ]);
        }

        return response()->json([
            'encontrado' => false,
            'equipo'     => null
        ]);
    }
}
