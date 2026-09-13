<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Contrato;
use App\Models\MantenimientoChecklist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MantenimientoController extends Controller
{
    /**
     * Muestra la lista paginada de mantenimientos con filtros.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Mantenimiento::with(['equipo', 'tecnico', 'contrato.cliente'])
            ->latest('fecha_mantenimiento');

        // Si es TÉCNICO, solo ve los mantenimientos realizados por él mismo
        if ($user && $user->rol === 'TECNICO') {
            $query->where('tecnico_id', $user->id);
        }

        // Filtro por búsqueda (Serie, Inventario, Usuario, Técnico)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('equipo', function ($qe) use ($search) {
                    $qe->where('numero_serie', 'like', "%{$search}%")
                       ->orWhere('codigo_inventario', 'like', "%{$search}%")
                       ->orWhere('usuario_asignado', 'like', "%{$search}%")
                       ->orWhere('marca', 'like', "%{$search}%")
                       ->orWhere('modelo', 'like', "%{$search}%");
                })
                ->orWhereHas('tecnico', function ($qt) use ($search) {
                    $qt->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Filtro por Tipo de Equipo
        if ($tipo = $request->input('tipo_equipo')) {
            $query->whereHas('equipo', function ($q) use ($tipo) {
                $q->where('tipo_equipo', $tipo);
            });
        }

        // Filtro por Estado de Impresión
        if ($request->has('impreso') && $request->input('impreso') !== null && $request->input('impreso') !== '') {
            $query->where('impreso', $request->input('impreso'));
        }

        $mantenimientos = $query->paginate(24)->withQueryString();

        return Inertia::render('Mantenimientos/Index', [
            'mantenimientos' => $mantenimientos,
            'filters' => $request->only(['search', 'tipo_equipo', 'impreso']),
        ]);
    }

    /**
     * Formulario para crear un nuevo mantenimiento en sitio.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $contratoId = $request->input('contrato_id');
        $serie = $request->input('serie');

        $query = Contrato::with('cliente')->where('estado', 'ACTIVO');

        if ($contratoId) {
            $query->where('id', $contratoId);
        } elseif ($user && $user->rol === 'TECNICO') {
            $query->whereHas('tecnicos', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $contratoActivo = $query->first() ?? Contrato::with('cliente')->where('estado', 'ACTIVO')->first();

        return Inertia::render('Mantenimientos/Create', [
            'contratoActivo' => $contratoActivo,
            'serieInicial'   => $serie,
        ]);
    }

    /**
     * Almacena el mantenimiento y registra/actualiza el equipo en caliente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contrato_id'         => 'required|exists:contratos,id',
            'numero_serie'        => 'required|string|max:100',
            'codigo_inventario'   => 'nullable|string|max:50',
            'tipo_equipo'         => 'required|in:DESKTOP,LAPTOP,IMPRESORA,OTRO',
            'marca'               => 'required|string|max:50',
            'modelo'              => 'required|string|max:50',
            'departamento_unidad' => 'nullable|string|max:100',
            'usuario_asignado'    => 'nullable|string|max:100',
            'observaciones'       => 'nullable|string',
            'recomendaciones'     => 'nullable|string',
            'checklists'          => 'nullable|array',
            'checklists.*.item'   => 'required|string',
            'checklists.*.hecho'  => 'required|boolean',
            'checklists.*.nota'   => 'nullable|string',
        ]);

        $mantenimiento = null;

        DB::transaction(function () use ($validated, &$mantenimiento) {
            // 1. Crear o actualizar datos del Equipo en caliente
            $equipo = Equipo::updateOrCreate(
                ['numero_serie' => trim($validated['numero_serie'])],
                [
                    'codigo_inventario'   => $validated['codigo_inventario'] ?? null,
                    'tipo_equipo'         => $validated['tipo_equipo'],
                    'marca'               => $validated['marca'],
                    'modelo'              => $validated['modelo'],
                    'departamento_unidad' => $validated['departamento_unidad'] ?? null,
                    'usuario_asignado'    => $validated['usuario_asignado'] ?? null,
                    'actualizado_por'     => Auth::id(),
                ]
            );

            // 2. Crear el Mantenimiento
            $mantenimiento = Mantenimiento::create([
                'contrato_id'         => $validated['contrato_id'],
                'equipo_id'           => $equipo->id,
                'tecnico_id'          => Auth::id() ?? 1,
                'fecha_mantenimiento' => now(),
                'observaciones'       => $validated['observaciones'] ?? null,
                'recomendaciones'     => $validated['recomendaciones'] ?? null,
                'estado_firma'        => 'PENDIENTE',
                'impreso'             => 0,
                'creado_por'          => Auth::id(),
            ]);

            // 3. Registrar ítems del Checklist
            if (!empty($validated['checklists'])) {
                foreach ($validated['checklists'] as $chk) {
                    MantenimientoChecklist::create([
                        'mantenimiento_id'  => $mantenimiento->id,
                        'item_verificacion' => $chk['item'],
                        'realizado'         => $chk['hecho'] ? 1 : 0,
                        'comentarios'       => $chk['nota'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('mantenimientos.index')
            ->with('success', '¡Mantenimiento registrado exitosamente!');
    }

    /**
     * Muestra el detalle de un mantenimiento.
     */
    public function show(Mantenimiento $mantenimiento)
    {
        $user = Auth::user();

        // REGLA DE NEGOCIO: Un técnico SOLO puede visualizar sus propios mantenimientos
        if ($user && $user->rol === 'TECNICO' && $mantenimiento->tecnico_id !== $user->id) {
            return redirect()->route('mantenimientos.index')
                ->with('error', 'Solo puedes consultar los mantenimientos que tú mismo registraste.');
        }

        $mantenimiento->load(['equipo', 'tecnico', 'contrato.cliente', 'checklists', 'sede', 'impresoPor']);

        return Inertia::render('Mantenimientos/Show', [
            'mantenimiento' => $mantenimiento,
        ]);
    }

    /**
     * Formulario para editar un mantenimiento existente.
     */
    public function edit(Mantenimiento $mantenimiento)
    {
        $user = Auth::user();

        // REGLA DE NEGOCIO: Un técnico SOLO puede editar sus propios mantenimientos
        if ($user->rol === 'TECNICO' && $mantenimiento->tecnico_id !== $user->id) {
            return redirect()->route('mantenimientos.index')
                ->with('error', 'Solo puedes editar los mantenimientos que tú mismo registraste.');
        }

        $mantenimiento->load(['equipo', 'contrato.cliente', 'checklists']);

        return Inertia::render('Mantenimientos/Edit', [
            'mantenimiento' => $mantenimiento
        ]);
    }

    /**
     * Actualiza un mantenimiento existente.
     */
    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $user = Auth::user();

        if ($user->rol === 'TECNICO' && $mantenimiento->tecnico_id !== $user->id) {
            return redirect()->route('mantenimientos.index')
                ->with('error', 'Solo puedes editar los mantenimientos que tú mismo registraste.');
        }

        $validated = $request->validate([
            'observaciones'     => 'nullable|string',
            'recomendaciones'   => 'nullable|string',
            'estado_firma'      => 'required|in:PENDIENTE,FIRMADO_FISICO',
            'checklists'        => 'nullable|array',
            'checklists.*.id'   => 'nullable|integer',
            'checklists.*.item' => 'required|string',
            'checklists.*.hecho'=> 'required|boolean',
            'checklists.*.nota' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $mantenimiento) {
            $mantenimiento->update([
                'observaciones'   => $validated['observaciones'] ?? null,
                'recomendaciones' => $validated['recomendaciones'] ?? null,
                'estado_firma'    => $validated['estado_firma'],
                'actualizado_por' => Auth::id(),
            ]);

            if (isset($validated['checklists'])) {
                foreach ($validated['checklists'] as $chk) {
                    MantenimientoChecklist::updateOrCreate(
                        [
                            'id'               => $chk['id'] ?? null,
                            'mantenimiento_id' => $mantenimiento->id,
                        ],
                        [
                            'item_verificacion' => $chk['item'],
                            'realizado'         => $chk['hecho'] ? 1 : 0,
                            'comentarios'       => $chk['nota'] ?? null,
                        ]
                    );
                }
            }
        });

        return redirect()->route('mantenimientos.index')
            ->with('success', 'Mantenimiento actualizado correctamente.');
    }

    /**
     * Marca el reporte PDF como impreso.
     */
    public function marcarImpreso(Mantenimiento $mantenimiento)
    {
        $mantenimiento->update([
            'impreso'         => 1,
            'fecha_impresion' => now(),
            'impreso_por'     => Auth::id(),
            'estado_firma'    => 'FIRMADO_FISICO',
        ]);

        return back()->with('success', 'El reporte ha sido marcado como impreso.');
    }
}
