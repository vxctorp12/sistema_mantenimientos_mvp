<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Sede;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cliente::with('sedes')->withCount(['sedes', 'contratos']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre_cliente', 'like', "%{$search}%")
                  ->orWhere('contacto_nombre', 'like', "%{$search}%");
        }

        $clientes = $query->orderBy('nombre_cliente', 'asc')->paginate(24)->withQueryString();

        return Inertia::render('Admin/Clientes/Index', [
            'clientes' => $clientes,
            'filters'  => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_cliente'  => 'required|string|max:150',
            'contacto_nombre' => 'nullable|string|max:100',
            'contacto_email'  => 'nullable|email|max:100',
            'telefono'        => 'nullable|string|max:30',
        ]);

        Cliente::create($validated);

        return redirect()->back()->with('success', 'Cliente registrado correctamente.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nombre_cliente'  => 'required|string|max:150',
            'contacto_nombre' => 'nullable|string|max:100',
            'contacto_email'  => 'nullable|email|max:100',
            'telefono'        => 'nullable|string|max:30',
        ]);

        $cliente->update($validated);

        return redirect()->back()->with('success', 'Datos del cliente actualizados.');
    }

    public function storeSede(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nombre_sede' => 'required|string|max:100',
            'direccion'   => 'nullable|string|max:255',
            'telefono'    => 'nullable|string|max:30',
            'latitud'     => 'nullable|numeric',
            'longitud'    => 'nullable|numeric',
        ]);

        $cliente->sedes()->create($validated);

        return redirect()->back()->with('success', 'Sede agregada exitosamente.');
    }

    public function updateSede(Request $request, Sede $sede)
    {
        $validated = $request->validate([
            'nombre_sede' => 'required|string|max:100',
            'direccion'   => 'nullable|string|max:255',
            'telefono'    => 'nullable|string|max:30',
            'latitud'     => 'nullable|numeric',
            'longitud'    => 'nullable|numeric',
        ]);

        $sede->update($validated);

        return redirect()->back()->with('success', 'Sede actualizada exitosamente.');
    }

    public function destroySede(Sede $sede)
    {
        $sede->delete();
        return redirect()->back()->with('success', 'Sede eliminada exitosamente.');
    }
}
