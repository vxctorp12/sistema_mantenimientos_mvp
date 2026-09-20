<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\EquipoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Contratos
    Route::get('/contratos/{contrato}/dashboard', [DashboardController::class, 'contratoDashboard'])->name('contratos.dashboard');
    Route::get('/contratos/{contrato}/detalles', [DashboardController::class, 'contratoDetalles'])->name('contratos.detalles');

    // Mantenimientos
    Route::get('/mantenimientos', [MantenimientoController::class, 'index'])->name('mantenimientos.index');
    Route::get('/mantenimientos/nuevo', [MantenimientoController::class, 'create'])->name('mantenimientos.create');
    Route::post('/mantenimientos', [MantenimientoController::class, 'store'])->name('mantenimientos.store');
    Route::get('/mantenimientos/{mantenimiento}', [MantenimientoController::class, 'show'])->name('mantenimientos.show');
    Route::get('/mantenimientos/{mantenimiento}/edit', [MantenimientoController::class, 'edit'])->name('mantenimientos.edit');
    Route::get('/mantenimientos/{mantenimiento}/editar', [MantenimientoController::class, 'edit']);
    Route::put('/mantenimientos/{mantenimiento}', [MantenimientoController::class, 'update'])->name('mantenimientos.update');
    Route::get('/mantenimientos/{mantenimiento}/pdf', [MantenimientoController::class, 'descargarPdf'])->name('mantenimientos.pdf');
    Route::get('/mantenimientos-exportar-pdf-masivo', [MantenimientoController::class, 'exportarPdfMasivo'])->name('mantenimientos.exportar-pdf-masivo');
    Route::post('/mantenimientos/{mantenimiento}/marcar-impreso', [MantenimientoController::class, 'marcarImpreso'])->name('mantenimientos.marcar-impreso');

    // Búsqueda por Serie / Inventario para Autocompletado en caliente
    Route::get('/api/equipos/buscar/{serie}', [EquipoController::class, 'buscarPorSerie'])->name('equipos.buscar');
    Route::get('/api/equipos/buscar-inventario', [EquipoController::class, 'buscarPorInventario'])->name('equipos.buscar-inventario');

    // Inventario de Equipos
    Route::get('/equipos', [EquipoController::class, 'index'])->name('equipos.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\ContratoController;
use App\Http\Controllers\Admin\ReporteController;

Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    // Gestión de Usuarios
    Route::resource('usuarios', UserController::class)->parameters(['usuarios' => 'user'])->only(['index', 'store', 'update']);
    Route::patch('usuarios/{user}/toggle-state', [UserController::class, 'toggleState'])->name('usuarios.toggle-state');

    // Gestión de Clientes y Sedes
    Route::resource('clientes', ClienteController::class)->only(['index', 'store', 'update']);
    Route::post('clientes/{cliente}/sedes', [ClienteController::class, 'storeSede'])->name('clientes.sedes.store');

    // Gestión de Contratos y Asignación de Personal
    Route::resource('contratos', ContratoController::class)->only(['index', 'store', 'update']);
    Route::post('contratos/{contrato}/asignar-tecnicos', [ContratoController::class, 'asignarTecnicos'])->name('contratos.asignar-tecnicos');
    Route::patch('contratos/{contrato}/toggle-estado', [ContratoController::class, 'toggleEstado'])->name('contratos.toggle-estado');

    // Exportación de Reportes Consolidados
    Route::get('contratos/{contrato}/exportar-excel', [ReporteController::class, 'exportarExcel'])->name('contratos.exportar-excel');
});

require __DIR__.'/auth.php';
