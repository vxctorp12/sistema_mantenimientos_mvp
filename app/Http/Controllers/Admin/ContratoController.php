<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contrato;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContratoController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::with(['cliente', 'tecnicos:id,name,email']);

        // Filtro por búsqueda general (cliente o ubicación)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('cliente', function ($qc) use ($search) {
                    $qc->where('nombre_cliente', 'like', "%{$search}%");
                })->orWhere('ubicacion_general', 'like', "%{$search}%");
            });
        }

        // Filtro por estado para poder ocultar del Dashboard los FINALIZADOS/CANCELADOS
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $contratos = $query->orderBy('creado_en', 'desc')->paginate(24)->withQueryString();
        $clientes  = Cliente::select('id', 'nombre_cliente')->get();
        $tecnicos  = User::where('rol', 'TECNICO')->where('activo', true)->select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Contratos/Index', [
            'contratos'     => $contratos,
            'clientes'      => $clientes,
            'tecnicos'      => $tecnicos,
            'listaTecnicos' => $tecnicos,
            'filters'       => $request->only(['search', 'estado'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'                => 'required|exists:clientes,id',
            'meta_equipos_total'        => 'required|integer|min:1',
            'mantenimientos_por_equipo' => 'required|integer|min:1',
            'fecha_inicio'              => 'required|date',
            'fecha_limite'              => 'nullable|date|after_or_equal:fecha_inicio',
            'ubicacion_general'         => 'nullable|string|max:255',
            'requerimientos_especiales' => 'nullable|string',
            'tecnicos'                  => 'array',
            'tecnicos.*'                => 'exists:users,id'
        ]);

        $contrato = Contrato::create($validated);

        if (!empty($validated['tecnicos'])) {
            $contrato->tecnicos()->sync($validated['tecnicos']);
        }

        return redirect()->back()->with('success', 'Contrato aperturado exitosamente.');
    }

    public function update(Request $request, Contrato $contrato)
    {
        $validated = $request->validate([
            'cliente_id'                => 'sometimes|required|exists:clientes,id',
            'meta_equipos_total'        => 'required|integer|min:1',
            'mantenimientos_por_equipo' => 'required|integer|min:1',
            'fecha_inicio'              => 'required|date',
            'fecha_limite'              => 'nullable|date',
            'ubicacion_general'         => 'nullable|string|max:255',
            'requerimientos_especiales' => 'nullable|string',
            'estado'                    => 'required|in:ACTIVO,FINALIZADO,CANCELADO',
            'tecnicos'                  => 'array',
            'tecnicos.*'                => 'exists:users,id'
        ]);

        $contrato->update($validated);

        if (isset($validated['tecnicos'])) {
            $contrato->tecnicos()->sync($validated['tecnicos']);
        }

        return redirect()->back()->with('success', 'Contrato actualizado correctamente.');
    }

    public function asignarTecnicos(Request $request, Contrato $contrato)
    {
        $validated = $request->validate([
            'tecnicos_ids'   => 'nullable|array',
            'tecnicos_ids.*' => 'exists:users,id'
        ]);

        $contrato->tecnicos()->sync($validated['tecnicos_ids'] ?? []);

        return redirect()->back()->with('success', 'Técnicos asignados correctamente.');
    }

    // Ocultar del Dashboard cambiando el estado a CANCELADO o FINALIZADO sin borrar registros
    public function toggleEstado(Contrato $contrato, Request $request)
    {
        $nuevoEstado = $request->input('estado', 'CANCELADO');
        $contrato->update(['estado' => $nuevoEstado]);

        return redirect()->back()->with('success', "Estado del contrato actualizado a {$nuevoEstado}.");
    }
}
