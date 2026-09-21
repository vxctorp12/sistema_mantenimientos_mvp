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
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Si es INVITADO, solo ve los mantenimientos de los contratos de su cliente
        if ($user && $user->rol === 'INVITADO') {
            $query->whereHas('contrato', function ($q) use ($user) {
                $q->where('cliente_id', $user->cliente_id);
            });
        }

        // Filtro por Rango de Fechas
        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->whereDate('fecha_mantenimiento', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->whereDate('fecha_mantenimiento', '<=', $fechaFin);
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
            'filters' => $request->only(['search', 'tipo_equipo', 'impreso', 'fecha_inicio', 'fecha_fin']),
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
        $codigoInventario = $request->input('codigo_inventario');

        // Obtener contratos activos asignados al técnico (o todos si es ADMIN)
        $query = Contrato::with('cliente')->where('estado', 'ACTIVO');

        if ($user && $user->rol === 'TECNICO') {
            $query->whereHas('tecnicos', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $contratos = $query->get();
        if ($contratos->isEmpty()) {
            $contratos = Contrato::with('cliente')->where('estado', 'ACTIVO')->get();
        }

        $contratoActivo = $contratoId 
            ? $contratos->firstWhere('id', $contratoId) 
            : $contratos->first();

        return Inertia::render('Mantenimientos/Create', [
            'contratos'           => $contratos,
            'contratoActivo'      => $contratoActivo,
            'contrato_id_default' => $contratoActivo?->id,
            'serieInicial'        => $serie,
            'inventarioInicial'   => $codigoInventario,
        ]);
    }

    /**
     * Almacena el mantenimiento y registra/actualiza el equipo en caliente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contrato_id'         => 'required|exists:contratos,id',
            'codigo_inventario'   => 'required|string|max:50',
            'numero_serie'        => 'required|string|max:100',
            'tipo_equipo'         => 'required|in:DESKTOP,LAPTOP,IMPRESORA,OTRO',
            'marca'               => 'required|string|max:50',
            'modelo'              => 'required|string|max:50',
            'departamento_unidad' => 'nullable|string|max:100',
            'usuario_asignado'    => 'nullable|string|max:100',
            'direccion_ip'        => 'nullable|string|max:45',
            'sistema_operativo'   => 'nullable|string|max:50',
            'contador_bn'         => 'nullable|integer',
            'contador_color'      => 'nullable|integer',
            'observaciones'       => 'nullable|string',
            'recomendaciones'     => 'nullable|string',
            'fecha_mantenimiento' => 'nullable|date',
            'tipo_mantenimiento'  => 'nullable|string|max:50',
            'checklist'           => 'nullable|array',
            'checklists'          => 'nullable|array',
        ]);

        $mantenimiento = null;

        DB::transaction(function () use ($validated, $request, &$mantenimiento) {
            // 1. Buscar o Registrar/Actualizar Equipo por Código de Inventario
            $equipo = Equipo::updateOrCreate(
                ['codigo_inventario' => trim($validated['codigo_inventario'])],
                [
                    'numero_serie'        => trim($validated['numero_serie']),
                    'tipo_equipo'         => $validated['tipo_equipo'],
                    'marca'               => $validated['marca'],
                    'modelo'              => $validated['modelo'],
                    'departamento_unidad' => $validated['departamento_unidad'] ?? null,
                    'usuario_asignado'    => $validated['usuario_asignado'] ?? null,
                    'direccion_ip'        => $validated['direccion_ip'] ?? null,
                    'sistema_operativo'   => $validated['sistema_operativo'] ?? null,
                    'actualizado_por'     => Auth::id(),
                ]
            );

            // 2. Crear el Mantenimiento (Verificar que no exista uno previo)
            if (Mantenimiento::where('equipo_id', $equipo->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'codigo_inventario' => 'Este equipo ya cuenta con un registro de mantenimiento. Por favor, edite el registro existente en lugar de crear uno nuevo.',
                    'numero_serie' => 'Este equipo ya cuenta con un registro de mantenimiento.'
                ]);
            }

            $mantenimiento = Mantenimiento::create([
                'contrato_id'         => $validated['contrato_id'],
                'equipo_id'           => $equipo->id,
                'tecnico_id'          => Auth::id() ?? 1,
                'fecha_mantenimiento' => $validated['fecha_mantenimiento'] ?? now(),
                'tipo_mantenimiento'  => $validated['tipo_mantenimiento'] ?? 'PREVENTIVO',
                'observaciones'       => $validated['observaciones'] ?? null,
                'recomendaciones'     => $validated['recomendaciones'] ?? null,
                'contador_bn'         => $validated['contador_bn'] ?? null,
                'contador_color'      => $validated['contador_color'] ?? null,
                'estado_firma'        => 'PENDIENTE',
                'impreso'             => false,
                'creado_por'          => Auth::id(),
            ]);

            // 3. Registrar ítems del Checklist Físico Reconstruido
            $itemsChecklist = $validated['checklist'] ?? $validated['checklists'] ?? [];

            foreach ($itemsChecklist as $chk) {
                $nombreVerificacion = $chk['nombre'] ?? $chk['item'] ?? $chk['item_verificacion'] ?? '';
                $seccion = $chk['seccion'] ?? $chk['categoria_seccion'] ?? 'GENERAL';
                $estado = $chk['estado'] ?? (isset($chk['hecho']) && $chk['hecho'] ? 'SI' : 'NO');
                $comentarioText = $chk['comentario'] ?? $chk['nota'] ?? $chk['comentarios'] ?? null;
                $esRealizado = ($estado === 'SI' || (isset($chk['hecho']) && $chk['hecho']));

                if (!empty($nombreVerificacion)) {
                    MantenimientoChecklist::create([
                        'mantenimiento_id'  => $mantenimiento->id,
                        'categoria_seccion' => $seccion,
                        'item_verificacion' => $nombreVerificacion,
                        'realizado'         => $esRealizado ? 1 : 0,
                        'estado'            => $estado,
                        'comentario'        => $comentarioText,
                        'comentarios'       => $comentarioText,
                    ]);
                }
            }
        });

        if ($request->input('imprimir_pdf')) {
            return redirect()->route('mantenimientos.pdf', $mantenimiento->id);
        }

        return redirect()->route('mantenimientos.index')
            ->with('success', '¡Mantenimiento y lista de verificación registrados correctamente!');
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
     * Formulario para editar un mantenimiento existente (v4 total edit).
     */
    public function edit($id)
    {
        $mantenimiento = Mantenimiento::with(['equipo', 'contrato.cliente', 'checklists', 'detalles', 'tecnico'])
            ->findOrFail($id);

        $user = Auth::user();

        // REGLA DE NEGOCIO: Un técnico SOLO puede editar sus propios mantenimientos; ADMIN edita cualquier registro
        if ($user && $user->rol === 'TECNICO' && $mantenimiento->tecnico_id !== $user->id) {
            return redirect()->route('mantenimientos.index')
                ->with('error', 'No tienes autorización para modificar reportes de otros técnicos.');
        }

        $contratos = ($user && $user->rol === 'ADMIN')
            ? Contrato::with('cliente')->where('estado', 'ACTIVO')->get()
            : ($user ? $user->contratos()->with('cliente')->where('estado', 'ACTIVO')->get() : Contrato::with('cliente')->get());

        if ($contratos->isEmpty()) {
            $contratos = Contrato::with('cliente')->where('estado', 'ACTIVO')->get();
        }

        return Inertia::render('Mantenimientos/Edit', [
            'mantenimiento' => $mantenimiento,
            'contratos'     => $contratos,
        ]);
    }

    /**
     * Actualiza TODOS los campos del mantenimiento y equipo asociado (v4).
     */
    public function update(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);
        $user = Auth::user();

        if ($user && $user->rol === 'TECNICO' && $mantenimiento->tecnico_id !== $user->id) {
            return redirect()->route('mantenimientos.index')
                ->with('error', 'Solo puedes editar los mantenimientos que tú mismo registraste.');
        }

        $validated = $request->validate([
            // Datos del Equipo
            'codigo_inventario'    => 'required|string|max:100',
            'numero_serie'         => 'required|string|max:100',
            'tipo_equipo'          => 'required|in:DESKTOP,LAPTOP,IMPRESORA,OTRO',
            'marca'                => 'required|string|max:100',
            'modelo'               => 'required|string|max:100',
            'ubicacion_especifica' => 'nullable|string|max:150',
            'usuario_asignado'     => 'nullable|string|max:150',

            // Datos del Mantenimiento
            'contrato_id'          => 'required|exists:contratos,id',
            'fecha_mantenimiento'  => 'required|date',
            'tipo_mantenimiento'   => 'nullable|string',
            'contador_bn'          => 'nullable|integer|min:0',
            'contador_color'       => 'nullable|integer|min:0',
            'direccion_ip'         => 'nullable|string|max:45',
            'diagnostico'          => 'nullable|string',
            'trabajo_realizado'    => 'nullable|string',
            'observaciones'        => 'nullable|string',
            'recomendaciones'      => 'nullable|string',
            'estado_equipo'        => 'nullable|string',
            'estado_firma'         => 'nullable|string',
            'impreso'              => 'nullable|boolean',

            // Checklist dinámico
            'checklist'            => 'nullable|array',
            'checklists'           => 'nullable|array',
        ]);

        DB::transaction(function () use ($mantenimiento, $validated) {
            // 1. Actualizar datos del Equipo
            if ($mantenimiento->equipo_id) {
                $equipo = Equipo::find($mantenimiento->equipo_id);
                if ($equipo) {
                    $equipo->update([
                        'codigo_inventario'    => trim($validated['codigo_inventario']),
                        'numero_serie'         => trim($validated['numero_serie']),
                        'tipo_equipo'          => $validated['tipo_equipo'],
                        'marca'                => $validated['marca'],
                        'modelo'               => $validated['modelo'],
                        'ubicacion_especifica' => $validated['ubicacion_especifica'] ?? $equipo->ubicacion_especifica,
                        'departamento_unidad' => $validated['ubicacion_especifica'] ?? $equipo->departamento_unidad,
                        'usuario_asignado'     => $validated['usuario_asignado'] ?? $equipo->usuario_asignado,
                        'direccion_ip'         => $validated['direccion_ip'] ?? $equipo->direccion_ip,
                        'actualizado_por'      => Auth::id(),
                    ]);
                }
            }

            // 2. Actualizar cabecera de Mantenimiento
            $mantenimiento->update([
                'contrato_id'         => $validated['contrato_id'],
                'fecha_mantenimiento' => $validated['fecha_mantenimiento'],
                'tipo_mantenimiento'  => $validated['tipo_mantenimiento'] ?? 'PREVENTIVO',
                'contador_bn'         => $validated['contador_bn'] ?? null,
                'contador_color'      => $validated['contador_color'] ?? null,
                'observaciones'       => $validated['observaciones'] ?? null,
                'recomendaciones'     => $validated['recomendaciones'] ?? $validated['trabajo_realizado'] ?? null,
                'estado_firma'        => $validated['estado_firma'] ?? $mantenimiento->estado_firma ?? 'PENDIENTE',
                'impreso'             => isset($validated['impreso']) ? (bool)$validated['impreso'] : $mantenimiento->impreso,
                'actualizado_por'     => Auth::id(),
            ]);

            // 3. Reemplazar/Actualizar puntos del Checklist
            $itemsChecklist = $validated['checklist'] ?? $validated['checklists'] ?? [];
            if (!empty($itemsChecklist)) {
                MantenimientoChecklist::where('mantenimiento_id', $mantenimiento->id)->delete();
                foreach ($itemsChecklist as $item) {
                    $punto = $item['nombre'] ?? $item['punto'] ?? $item['item'] ?? $item['punto_chequeo'] ?? $item['item_verificacion'] ?? '';
                    $estado = $item['estado'] ?? (isset($item['hecho']) && $item['hecho'] ? 'SI' : 'NO');
                    $notas = $item['comentario'] ?? $item['comentarios'] ?? $item['notas'] ?? $item['observacion'] ?? null;
                    $seccion = $item['seccion'] ?? $item['categoria_seccion'] ?? 'GENERAL';

                    if (!empty($punto)) {
                        MantenimientoChecklist::create([
                            'mantenimiento_id'  => $mantenimiento->id,
                            'categoria_seccion' => $seccion,
                            'item_verificacion' => $punto,
                            'realizado'         => ($estado === 'SI' || (isset($item['hecho']) && $item['hecho'])) ? 1 : 0,
                            'estado'            => $estado,
                            'comentario'        => $notas,
                            'comentarios'       => $notas,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('mantenimientos.index')
            ->with('success', 'Mantenimiento y datos del equipo modificados correctamente.');
    }

    /**
     * Generar / Descargar PDF para impresión y firma física (v4).
     */
    public function descargarPdf($id)
    {
        $mantenimiento = Mantenimiento::with([
            'equipo', 
            'contrato.cliente', 
            'checklists',
            'detalles', 
            'tecnico'
        ])->findOrFail($id);

        // Marcar como impreso
        $mantenimiento->update([
            'impreso'         => true,
            'fecha_impresion' => now(),
            'impreso_por'     => Auth::id(),
            'estado_firma'    => 'FIRMADO_FISICO',
        ]);

        $codigo = $mantenimiento->equipo->codigo_inventario ?? $mantenimiento->equipo->numero_serie ?? ('ID-'.$mantenimiento->id);
        $codigoLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', trim($codigo));
        $fechaLimpia = $mantenimiento->fecha_mantenimiento ? date('Ymd', strtotime($mantenimiento->fecha_mantenimiento)) : date('Ymd');
        $nombreArchivo = "Reporte_Mantenimiento_{$codigoLimpio}_{$fechaLimpia}";

        $logoBase64 = $this->getLogoBase64();
        $watermarkBase64 = $this->getWatermarkBase64();

        return view('pdf.hoja_servicio', [
            'mantenimiento'   => $mantenimiento,
            'tituloDocumento' => $nombreArchivo,
            'logoBase64'      => $logoBase64,
            'watermarkBase64' => $watermarkBase64,
        ]);
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

    /**
     * Generar / Descargar PDF Masivo Consolidado según los filtros aplicados en el listado.
     */
    public function exportarPdfMasivo(Request $request)
    {
        $user = Auth::user();
        $query = Mantenimiento::with([
            'equipo', 
            'contrato.cliente', 
            'checklists', 
            'detalles', 
            'tecnico'
        ])->latest('fecha_mantenimiento');

        // Si es TÉCNICO, solo exporta los mantenimientos realizados por él mismo
        if ($user && $user->rol === 'TECNICO') {
            $query->where('tecnico_id', $user->id);
        }

        // Si es INVITADO, solo exporta los mantenimientos de su cliente
        if ($user && $user->rol === 'INVITADO') {
            $query->whereHas('contrato', function ($q) use ($user) {
                $q->where('cliente_id', $user->cliente_id);
            });
        }

        // Filtro por Rango de Fechas
        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->whereDate('fecha_mantenimiento', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->whereDate('fecha_mantenimiento', '<=', $fechaFin);
        }

        // Filtro por Búsqueda
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

        $mantenimientos = $query->get();

        if ($mantenimientos->isEmpty()) {
            return back()->with('error', 'No se encontraron reportes de mantenimiento para exportar en PDF.');
        }

        // Marcar los reportes incluidos como impresos
        $ids = $mantenimientos->pluck('id');
        Mantenimiento::whereIn('id', $ids)->update([
            'impreso'         => true,
            'fecha_impresion' => now(),
            'impreso_por'     => Auth::id(),
            'estado_firma'    => 'FIRMADO_FISICO',
        ]);

        // Construir nombre dinámico automático según los filtros aplicados
        $partesNombre = ['Reportes_Mantenimiento'];

        if ($tipo = $request->input('tipo_equipo')) {
            $partesNombre[] = preg_replace('/[^A-Za-z0-9\-]/', '_', trim($tipo));
        }

        if ($fechaInicio = $request->input('fecha_inicio')) {
            $partesNombre[] = 'del_' . date('Ymd', strtotime($fechaInicio));
        }

        if ($fechaFin = $request->input('fecha_fin')) {
            $partesNombre[] = 'al_' . date('Ymd', strtotime($fechaFin));
        }

        if ($search = $request->input('search')) {
            $cleanSearch = preg_replace('/[^A-Za-z0-9\-]/', '_', trim($search));
            if (!empty($cleanSearch)) {
                $partesNombre[] = 'Filtro_' . substr($cleanSearch, 0, 15);
            }
        }

        if (count($partesNombre) === 1) {
            $partesNombre[] = 'Consolidados_' . date('Ymd');
        }

        $nombreArchivo = implode('_', $partesNombre);

        $logoBase64 = $this->getLogoBase64();
        $watermarkBase64 = $this->getWatermarkBase64();

        return view('pdf.hoja_servicio_batch', [
            'mantenimientos'  => $mantenimientos,
            'tituloDocumento' => $nombreArchivo,
            'logoBase64'      => $logoBase64,
            'watermarkBase64' => $watermarkBase64,
        ]);
    }

    /**
     * Retorna la imagen del logo en formato base64 asegurando su existencia local.
     */
    protected function getLogoBase64(): string
    {
        $logoPath = public_path('images/logo-rilaz.png');
        if (file_exists($logoPath) && filesize($logoPath) > 0) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        try {
            $ch = curl_init('https://rilaz.com.sv/wp-content/uploads/2026/02/Logo2021-2.png');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $imageData = curl_exec($ch);
            curl_close($ch);

            if ($imageData) {
                if (!file_exists(public_path('images'))) {
                    @mkdir(public_path('images'), 0755, true);
                }
                @file_put_contents($logoPath, $imageData);
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
        } catch (\Throwable $e) {
            // Ignorar y retornar cadena vacía si falla
        }

        return '';
    }

    /**
     * Retorna únicamente la parte del ícono (sin texto) para la marca de agua.
     */
    protected function getWatermarkBase64(): string
    {
        $iconPath = public_path('images/logo-watermark-icon.png');
        if (file_exists($iconPath) && filesize($iconPath) > 0) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath));
        }

        return $this->getLogoBase64();
    }
}

