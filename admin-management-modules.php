<?php

/**
 * ==============================================================================
 * MÓDULOS DEL ROL ADMINISTRADOR (Laravel 11 + Inertia.js + Vue 3 + Tailwind CSS)
 * Sistema de Gestión de Mantenimientos Preventivos RILAZ
 * ==============================================================================
 * 
 * Contenido:
 * 1. UserController.php        - CRUD de Usuarios y asignación de roles/estado
 * 2. ClienteController.php     - CRUD de Clientes y Sedes
 * 3. ContratoController.php    - Alta, Edición, Asignación de Técnicos y Ocultar/Cancelar
 * 4. ReporteController.php     - Exportación consolidada a Excel y PDF
 * 5. Componentes Vue 3 (Composition API) para el Panel Administrativo
 */

// ==============================================================================
// 1. BACKEND: UserController.php (app/Http/Controllers/Admin/UserController.php)
// ==============================================================================
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('cliente');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->input('rol'));
        }

        $usuarios = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $clientes = Cliente::select('id', 'nombre_cliente')->get();

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'clientes' => $clientes,
            'filters'  => $request->only(['search', 'rol'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|max:100|unique:users,email',
            'password'   => 'required|string|min:8',
            'rol'        => ['required', Rule::in(['ADMIN', 'TECNICO', 'INVITADO'])],
            'cliente_id' => 'nullable|required_if:rol,INVITADO|exists:clientes,id',
            'activo'     => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['activo']   = $request->boolean('activo', true);

        User::create($validated);

        return redirect()->back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => ['required', 'email', 'max:100', Rule::unique('users')->ignore($user->id)],
            'password'   => 'nullable|string|min:8',
            'rol'        => ['required', Rule::in(['ADMIN', 'TECNICO', 'INVITADO'])],
            'cliente_id' => 'nullable|required_if:rol,INVITADO|exists:clientes,id',
            'activo'     => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleState(User $user)
    {
        $user->update(['activo' => !$user->activo]);

        $status = $user->activo ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Usuario {$status} correctamente.");
    }
}


// ==============================================================================
// 2. BACKEND: ClienteController.php (app/Http/Controllers/Admin/ClienteController.php)
// ==============================================================================
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
        $query = Cliente::withCount(['sedes', 'contratos']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre_cliente', 'like', "%{$search}%")
                  ->orWhere('contacto_nombre', 'like', "%{$search}%");
        }

        $clientes = $query->orderBy('nombre_cliente', 'asc')->paginate(15)->withQueryString();

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
}


// ==============================================================================
// 3. BACKEND: ContratoController.php (app/Http/Controllers/Admin/ContratoController.php)
// ==============================================================================
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
        $query = Contrato::with(['cliente', 'tecnicos:id,name']);

        // Filtro por estado para poder ocultar del Dashboard los FINALIZADOS/CANCELADOS
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $contratos = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $clientes  = Cliente::select('id', 'nombre_cliente')->get();
        $tecnicos  = User::where('rol', 'TECNICO')->where('activo', true)->select('id', 'name')->get();

        return Inertia::render('Admin/Contratos/Index', [
            'contratos' => $contratos,
            'clientes'  => $clientes,
            'tecnicos'  => $tecnicos,
            'filters'   => $request->only(['estado'])
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

    // Ocultar del Dashboard cambiando el estado a CANCELADO o FINALIZADO sin borrar registros
    public function toggleEstado(Contrato $contrato, Request $request)
    {
        $nuevoEstado = $request->input('estado', 'CANCELADO');
        $contrato->update(['estado' => $nuevoEstado]);

        return redirect()->back()->with('success', "Estado del contrato actualizado a {$nuevoEstado}.");
    }
}


// ==============================================================================
// 4. BACKEND: ReporteController.php (app/Http/Controllers/Admin/ReporteController.php)
// ==============================================================================
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


// ==============================================================================
// 5. RUTAS DE LARAVEL (routes/admin.php o routes/web.php)
// ==============================================================================
/*
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\ContratoController;
use App\Http\Controllers\Admin\ReporteController;

Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    // Gestión de Usuarios
    Route::resource('usuarios', UserController::class)->only(['index', 'store', 'update']);
    Route::patch('usuarios/{user}/toggle-state', [UserController::class, 'toggleState'])->name('usuarios.toggle-state');

    // Gestión de Clientes y Sedes
    Route::resource('clientes', ClienteController::class)->only(['index', 'store', 'update']);
    Route::post('clientes/{cliente}/sedes', [ClienteController::class, 'storeSede'])->name('clientes.sedes.store');

    // Gestión de Contratos y Asignación de Personal
    Route::resource('contratos', ContratoController::class)->only(['index', 'store', 'update']);
    Route::patch('contratos/{contrato}/toggle-estado', [ContratoController::class, 'toggleEstado'])->name('contratos.toggle-estado');

    // Exportación de Reportes Consolidados
    Route::get('contratos/{contrato}/exportar-excel', [ReporteController::class, 'exportarExcel'])->name('contratos.exportar-excel');
});
*/
