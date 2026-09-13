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
        $query = Equipo::withCount('mantenimientos')->latest('actualizado_en');

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

        $pendientesCount = Equipo::doesntHave('mantenimientos')->count();
        $totalCount = Equipo::count();

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
        $equipo = Equipo::where('numero_serie', trim($serie))->first();

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
}
